<?php

namespace App\Libraries;

use App\Models\CartonsModel;
use App\Models\GamesModel;
use App\Models\NumbersCartonsModel;
use App\Models\SingsModel;
use App\Models\UsersModel;
use Config\Database;

class BotManager
{
    private static array $firstNames = [
        'Alejandro', 'Mateo', 'Santiago', 'Sebastián', 'Lucas', 'Martín', 'Daniel', 'Leonardo',
        'Diego', 'Nicolás', 'Joaquín', 'Samuel', 'Gabriel', 'Tomás', 'Emiliano', 'Maximiliano',
        'Benjamín', 'Felipe', 'Ignacio', 'Ángel', 'David', 'Javier', 'Adrián', 'Pablo',
        'Manuel', 'Álvaro', 'Andrés', 'Hugo', 'Fernando', 'Christian', 'Jorge', 'Ricardo',
        'Eduardo', 'Enrique', 'Raúl', 'Mario', 'Gonzalo', 'César', 'Rubén', 'Víctor',
        'Iván', 'Óscar', 'Héctor', 'Sergio', 'Marcos', 'Jesús', 'Jaime', 'Guillermo',
        'Alfonso', 'Julio', 'Sofía', 'Valentina', 'Isabella', 'Camila', 'Mariana', 'Luciana',
        'Gabriela', 'Sara', 'Daniela', 'Valeria', 'Victoria', 'Martina', 'Emma', 'Lucía',
        'Catalina', 'Elena', 'Emilia', 'Natalia', 'Paula', 'Juana', 'Juliana', 'Andrea',
        'Carolina', 'Florencia', 'Antonella', 'Renata', 'Amanda', 'Alma', 'Bianca', 'Clara',
        'Miranda', 'Paulina', 'Julieta', 'Abril', 'Rocío', 'Constanza', 'Montserrat', 'Agustina',
        'Salomé', 'Guadalupe', 'Dulce', 'Fernanda', 'Regina', 'Ximena', 'Zoe', 'Paloma',
        'Ivanna', 'Aitana', 'Mía', 'Carmen'
    ];

    private static array $lastNames = [
        'García', 'Rodríguez', 'González', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez',
        'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández', 'Díaz', 'Moreno', 'Álvarez',
        'Muñoz', 'Romero', 'Alonso', 'Gutiérrez', 'Navarro', 'Torres', 'Domínguez', 'Vázquez',
        'Ramos', 'Gil', 'Ramírez', 'Serrano', 'Blanco', 'Molina', 'Morales', 'Suárez',
        'Ortega', 'Delgado', 'Castro', 'Ortiz', 'Rubio', 'Marín', 'Sanz', 'Núñez',
        'Iglesias', 'Medina', 'Garrido', 'Cortés', 'Castillo', 'Santos', 'Lozano', 'Guerrero',
        'Cano', 'Prieto', 'Méndez', 'Cruz', 'Calvo', 'Gallego', 'Vidal', 'León',
        'Herrera', 'Márquez', 'Peña', 'Flores', 'Cabrera', 'Campos', 'Vega', 'Fuentes',
        'Carrasco', 'Díez', 'Caballero', 'Reyes', 'Nieto', 'Aguilar', 'Pascual', 'Santana',
        'Herrero', 'Montero', 'Lorenzo', 'Hidalgo', 'Giménez', 'Ibáñez', 'Ferrer', 'Durán',
        'Santiago', 'Benítez', 'Vargas', 'Mora', 'Vicente', 'Arias', 'Carmona', 'Crespo',
        'Román', 'Pastor', 'Soto', 'Sáez', 'Velasco', 'Moya', 'Soler', 'Parra',
        'Esteban', 'Bravo', 'Gallardo', 'Rojas'
    ];

    /**
     * Asegura la existencia de al menos $count usuarios bots en la BD.
     * Si no existen, los crea con nombres reales, saldo y marcado automático.
     */
    public function ensureBots(int $count = 500): array
    {
        $modelUsers = new UsersModel();
        $db = Database::connect();

        $existingBots = $modelUsers->select('id, code, username, firstname, lastname')
            ->like('code', 'BOT-', 'after')
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->findAll();

        $existingCount = count($existingBots);
        if ($existingCount >= $count) {
            return array_slice($existingBots, 0, $count);
        }

        $needed = $count - $existingCount;
        $startIndex = $existingCount + 1;
        $now = date('Y-m-d H:i:s');
        $defaultPassword = password_hash('BotReyBingo2026!', PASSWORD_DEFAULT);

        $fnCount = count(self::$firstNames);
        $lnCount = count(self::$lastNames);

        $batch = [];
        for ($i = 0; $i < $needed; $i++) {
            $botNumber = $startIndex + $i;
            $code = sprintf('BOT-%04d', $botNumber);
            $username = sprintf('bot_player_%04d', $botNumber);

            $fn = self::$firstNames[($botNumber * 7) % $fnCount];
            $ln = self::$lastNames[($botNumber * 13) % $lnCount];

            $batch[] = [
                'code' => $code,
                'group' => 0, // Jugador
                'firstname' => $fn,
                'lastname' => $ln,
                'document' => sprintf('BOT%06d', $botNumber),
                'username' => $username,
                'phone' => sprintf('58%08d', 90000000 + $botNumber),
                'email' => sprintf('bot_%04d@reybingo.local', $botNumber),
                'password' => $defaultPassword,
                'wallet' => 5000.00,
                'wallet_recharge' => 5000.00,
                'wallet_withdraw' => 0.00,
                'wallet_bonus' => 0.00,
                'referred_code' => strtoupper(substr(md5($code), 0, 8)),
                'verified_email' => 1,
                'status' => 1,
                'deleted' => 0,
                'sounds' => 0,
                'narration' => 0,
                'autodial' => 1, // Marcado automático activado para cantar en cron
                'roulette' => 1,
                'terms_accepted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ];

            // Inserción en bloques de 100 para eficiencia
            if (count($batch) >= 100) {
                $db->table('users')->insertBatch($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $db->table('users')->insertBatch($batch);
        }

        // Recuperar la lista completa de bots
        return $modelUsers->select('id, code, username, firstname, lastname')
            ->like('code', 'BOT-', 'after')
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->findAll($count);
    }

    /**
     * Asigna N bots a una partida específica, cada uno con $cartonsPerBot cartones válidos de Bingo 75.
     */
    public function assignBotsToGame(int $gameId, int $botCount = 500, int $cartonsPerBot = 2, bool $forceCleanFirst = false): array
    {
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $db = Database::connect();

        $game = $modelGames->find($gameId);
        if (!$game) {
            return [
                'status' => 'error',
                'message' => 'Partida con ID #' . $gameId . ' no encontrada.'
            ];
        }

        if ($forceCleanFirst) {
            $this->clearBotsFromGame($gameId);
        }

        // 1. Asegurar la existencia de los bots requeridos
        $bots = $this->ensureBots($botCount);
        if (count($bots) < $botCount) {
            $botCount = count($bots);
        }

        // 2. Verificar cuántos cartones de bots ya están registrados en esta partida
        $botIds = array_column($bots, 'id');
        $existingBotCartons = $modelCartons->where('game', $gameId)
            ->whereIn('user', $botIds)
            ->countAllResults();

        if ($existingBotCartons >= ($botCount * $cartonsPerBot)) {
            return [
                'status' => 'info',
                'game_id' => $gameId,
                'bots_assigned' => $botCount,
                'cartons_count' => $existingBotCartons,
                'message' => "La partida #{$gameId} ya tiene {$existingBotCartons} cartones asignados a los bots."
            ];
        }

        // 3. Generar cartones y números en transacciones por lotes
        $totalCartonsCreated = 0;
        $now = date('Y-m-d H:i:s');

        // Procesar en chunks de 100 bots (200 cartones) por iteración para cuidar memoria
        $botChunks = array_chunk($bots, 100);

        foreach ($botChunks as $chunk) {
            $db->transStart();

            $cartonsBatch = [];
            foreach ($chunk as $bot) {
                $bId = (int) $bot['id'];
                for ($c = 0; $c < $cartonsPerBot; $c++) {
                    $cartonsBatch[] = [
                        'user' => $bId,
                        'game' => $gameId,
                        'status' => 1,
                        'pay_source' => 'wallet',
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            // Insertar bloque de cartones
            $db->table('cartons')->insertBatch($cartonsBatch);

            // Obtener los IDs de los cartones recién insertados para este bloque
            $currentCartonIds = $modelCartons->select('id')
                ->where('game', $gameId)
                ->whereIn('user', array_column($chunk, 'id'))
                ->where('serial', null)
                ->orWhere('serial', '')
                ->findColumn('id') ?? [];

            if (empty($currentCartonIds)) {
                // Si el motor no permite serial vacío, recuperamos los últimos N
                $currentCartonIds = $modelCartons->select('id')
                    ->where('game', $gameId)
                    ->whereIn('user', array_column($chunk, 'id'))
                    ->orderBy('id', 'DESC')
                    ->findAll(count($cartonsBatch));
                $currentCartonIds = array_column($currentCartonIds, 'id');
            }

            $numbersBatch = [];
            $updatesBatch = [];

            foreach ($currentCartonIds as $cartonId) {
                // Serial único de 9 dígitos: 6 dígitos de ID de cartón + 3 aleatorios
                $serial = str_pad($cartonId, 6, '0', STR_PAD_LEFT) . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                $updatesBatch[] = [
                    'id' => $cartonId,
                    'serial' => $serial
                ];

                // Generar los 25 números de la matriz Bingo 75
                $bCol = range(1, 15);
                $iCol = range(16, 30);
                $nCol = range(31, 45);
                $gCol = range(46, 60);
                $oCol = range(61, 75);

                shuffle($bCol);
                shuffle($iCol);
                shuffle($nCol);
                shuffle($gCol);
                shuffle($oCol);

                // Columna B (posiciones 1, 6, 11, 16, 21)
                for ($p = 0; $p < 5; $p++) {
                    $numbersBatch[] = [
                        'carton' => $cartonId,
                        'number' => $bCol[$p],
                        'position' => 1 + ($p * 5),
                        'status' => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                // Columna I (posiciones 2, 7, 12, 17, 22)
                for ($p = 0; $p < 5; $p++) {
                    $numbersBatch[] = [
                        'carton' => $cartonId,
                        'number' => $iCol[$p],
                        'position' => 2 + ($p * 5),
                        'status' => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                // Columna N (posiciones 3, 8, 13, 18, 23)
                // Posición 13 = centro libre (número 0, status 1)
                for ($p = 0; $p < 5; $p++) {
                    $pos = 3 + ($p * 5);
                    $isCenter = ($pos === 13);
                    $numbersBatch[] = [
                        'carton' => $cartonId,
                        'number' => $isCenter ? 0 : $nCol[$p],
                        'position' => $pos,
                        'status' => $isCenter ? 1 : 0, // Posición 13 siempre marcada
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                // Columna G (posiciones 4, 9, 14, 19, 24)
                for ($p = 0; $p < 5; $p++) {
                    $numbersBatch[] = [
                        'carton' => $cartonId,
                        'number' => $gCol[$p],
                        'position' => 4 + ($p * 5),
                        'status' => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                // Columna O (posiciones 5, 10, 15, 20, 25)
                for ($p = 0; $p < 5; $p++) {
                    $numbersBatch[] = [
                        'carton' => $cartonId,
                        'number' => $oCol[$p],
                        'position' => 5 + ($p * 5),
                        'status' => 0,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                $totalCartonsCreated++;
            }

            // Actualizar seriales
            if (!empty($updatesBatch)) {
                $db->table('cartons')->updateBatch($updatesBatch, 'id');
            }

            // Insertar números en bloques
            if (!empty($numbersBatch)) {
                $db->table('numbers')->insertBatch($numbersBatch);
            }

            $db->transComplete();
        }

        // 4. Limpiar caché de live status si existe
        $cache = cache();
        if ($cache) {
            $cache->delete('live_status_' . $gameId);
        }

        return [
            'status' => 'success',
            'game_id' => $gameId,
            'bots_assigned' => $botCount,
            'cartons_per_bot' => $cartonsPerBot,
            'total_cartons' => $totalCartonsCreated,
            'message' => "Se asignaron {$botCount} bots con {$cartonsPerBot} cartones cada uno ({$totalCartonsCreated} cartones en total) a la partida #{$gameId}."
        ];
    }

    /**
     * Limpia todos los cartones y números de los bots en una partida.
     */
    public function clearBotsFromGame(int $gameId): array
    {
        $modelCartons = new CartonsModel();
        $modelUsers = new UsersModel();
        $db = Database::connect();

        $botIds = $modelUsers->select('id')
            ->like('code', 'BOT-', 'after')
            ->findColumn('id') ?? [];

        if (empty($botIds)) {
            return [
                'status' => 'info',
                'game_id' => $gameId,
                'cartons_deleted' => 0,
                'message' => 'No hay usuarios bot registrados.'
            ];
        }

        $botCartonIds = $modelCartons->select('id')
            ->where('game', $gameId)
            ->whereIn('user', $botIds)
            ->findColumn('id') ?? [];

        $count = count($botCartonIds);
        if ($count > 0) {
            $db->transStart();

            // Eliminar números asociados
            $chunks = array_chunk($botCartonIds, 500);
            foreach ($chunks as $chunk) {
                $db->table('numbers')->whereIn('carton', $chunk)->delete();
            }

            // Eliminar cartones
            foreach ($chunks as $chunk) {
                $db->table('cartons')->whereIn('id', $chunk)->delete();
            }

            // Eliminar cantados previos de bots en este juego
            $db->table('sings')->where('game', $gameId)->whereIn('user', $botIds)->delete();

            $db->transComplete();
        }

        $cache = cache();
        if ($cache) {
            $cache->delete('live_status_' . $gameId);
        }

        return [
            'status' => 'success',
            'game_id' => $gameId,
            'cartons_deleted' => $count,
            'message' => "Se eliminaron {$count} cartones de bots de la partida #{$gameId}."
        ];
    }

    /**
     * Obtiene el estado actual de bots en una partida.
     */
    public function getGameBotsStatus(int $gameId): array
    {
        $modelCartons = new CartonsModel();
        $modelUsers = new UsersModel();

        $totalBots = $modelUsers->like('code', 'BOT-', 'after')->where('deleted', 0)->countAllResults();

        $botIds = $modelUsers->select('id')->like('code', 'BOT-', 'after')->findColumn('id') ?? [];

        $botCartonsInGame = 0;
        $distinctBotsInGame = 0;

        if (!empty($botIds)) {
            $botCartonsInGame = $modelCartons->where('game', $gameId)
                ->whereIn('user', $botIds)
                ->countAllResults();

            $distinctBotsInGame = $modelCartons->where('game', $gameId)
                ->whereIn('user', $botIds)
                ->select('user')
                ->distinct()
                ->countAllResults();
        }

        return [
            'game_id' => $gameId,
            'total_bots_in_db' => $totalBots,
            'bots_in_game' => $distinctBotsInGame,
            'cartons_in_game' => $botCartonsInGame
        ];
    }
}
