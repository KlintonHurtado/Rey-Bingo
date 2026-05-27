<?php

namespace App\Commands;

use App\Models\UsersModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class CreateTestPlayer extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'player:create-test';
    protected $description   = 'Crea jugador de prueba (group 0) y deja P2P desactivado en system';
    protected $usage         = 'player:create-test';

    public function run(array $params)
    {
        $username = 'jugador';
        $password = 'jugador123';

        $model = new UsersModel();
        $db    = Database::connect();

        $existing = $model->where('username', $username)->first();

        if ($existing) {
            $model->update($existing['id'], [
                'group'          => 0,
                'status'         => 1,
                'deleted'        => 0,
                'password'       => password_hash($password, PASSWORD_DEFAULT),
                'verified_email' => 1,
            ]);
            $userId = (int) $existing['id'];
            CLI::write('Jugador existente actualizado (id ' . $userId . ').', 'yellow');
        } else {
            $referredCode = strtoupper(bin2hex(random_bytes(4)));

            $model->insert([
                'group'          => 0,
                'firstname'      => 'Jugador',
                'lastname'       => 'Prueba',
                'document'       => '99999999',
                'username'       => $username,
                'phone'          => '5800000000',
                'email'          => 'jugador.prueba@reybingo.local',
                'password'       => password_hash($password, PASSWORD_DEFAULT),
                'wallet'         => 100,
                'wallet_recharge' => 100,
                'wallet_withdraw' => 0,
                'wallet_bonus'    => 0,
                'referred_code'  => $referredCode,
                'verified_email' => 1,
                'status'         => 1,
                'deleted'        => 0,
                'sounds'         => 1,
                'narration'      => 0,
                'autodial'       => 0,
                'roulette'       => 1,
            ]);

            $userId = (int) $model->getInsertID();
            $code   = 'BGC-A' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT);
            $model->update($userId, ['code' => $code]);

            CLI::write('Jugador de prueba creado (id ' . $userId . ', código ' . $code . ').', 'green');
        }

        $this->ensureSystemFlag($db, 'p2p_active', '0');
        $this->ensureSystemFlag($db, 'activateTransfer', '1');
        $this->ensureSystemFlag($db, 'activateDeposit', '1');
        $this->ensureSystemFlag($db, 'activateRetire', '1');

        CLI::newLine();
        CLI::write('Credenciales de jugador (pantalla de usuario):', 'cyan');
        CLI::write('  Usuario:  ' . $username);
        CLI::write('  Clave:    ' . $password);
        CLI::write('  Login:    ' . site_url('signin'));
        CLI::write('  Tras login va a: ' . site_url('play'));
        CLI::newLine();
        CLI::write('P2P oculto: p2p_active = 0 (botón P2P no debe verse en billetera).', 'green');
        CLI::write('Para mostrar P2P en prueba: UPDATE system SET value=1 WHERE `key`="p2p_active";', 'yellow');
    }

    private function ensureSystemFlag($db, string $key, string $value): void
    {
        $row = $db->table('system')->where('key', $key)->get()->getRow();

        if ($row) {
            $db->table('system')->where('key', $key)->update(['value' => $value]);
        } else {
            $db->table('system')->insert(['key' => $key, 'value' => $value]);
        }
    }
}
