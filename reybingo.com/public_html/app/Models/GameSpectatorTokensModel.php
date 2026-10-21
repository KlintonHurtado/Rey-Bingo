<?php

namespace App\Models;

use CodeIgniter\Model;

class GameSpectatorTokensModel extends Model
{
    protected $table = 'game_spectator_tokens';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'token',
        'game_id',
        'user_id',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ensureSchema(): void
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('game_spectator_tokens')) {
                $db->query("CREATE TABLE IF NOT EXISTS `game_spectator_tokens` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `token` varchar(64) NOT NULL,
                    `game_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_token` (`token`),
                    KEY `idx_game_user` (`game_id`, `user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in GameSpectatorTokensModel::ensureSchema: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene o genera un token único y seguro para que un jugador comparta su partida
     */
    public function getOrCreateToken(int $gameId, int $userId): string
    {
        $this->ensureSchema();

        $existing = $this->where('game_id', $gameId)
                         ->where('user_id', $userId)
                         ->first();

        if ($existing && !empty($existing['token'])) {
            return $existing['token'];
        }

        // Generar un token criptográficamente seguro de 32 caracteres hexadecimales
        $token = bin2hex(random_bytes(16));

        // Asegurar unicidad
        while ($this->where('token', $token)->countAllResults() > 0) {
            $token = bin2hex(random_bytes(16));
        }

        $this->insert([
            'token'   => $token,
            'game_id' => $gameId,
            'user_id' => $userId,
        ]);

        return $token;
    }

    /**
     * Busca los datos de partida y jugador asociados a un token
     */
    public function getShareInfoByToken(string $token): ?array
    {
        $this->ensureSchema();

        $cleanToken = trim($token);
        if ($cleanToken === '') {
            return null;
        }

        return $this->where('token', $cleanToken)->first();
    }
}
