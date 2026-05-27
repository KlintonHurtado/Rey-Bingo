<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Phase1WalletsAndKyc extends Migration
{
    public function up()
    {
        $fields = [
            'wallet_recharge' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'after'      => 'wallet',
            ],
            'wallet_withdraw' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'after'      => 'wallet_recharge',
            ],
            'wallet_bonus' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'after'      => 'wallet_withdraw',
            ],
            'kyc_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'after'      => 'wallet_bonus',
            ],
            'kyc_front' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'kyc_status',
            ],
            'kyc_back' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'kyc_front',
            ],
            'kyc_observations' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'kyc_back',
            ],
        ];

        if ($this->db->fieldExists('wallet', 'users')) {
            foreach ($fields as $name => $def) {
                if (! $this->db->fieldExists($name, 'users')) {
                    $this->forge->addColumn('users', [$name => $def]);
                }
            }

            // Migrar saldo legacy a recarga si las nuevas columnas están en cero
            $this->db->query(
                'UPDATE users SET wallet_recharge = wallet 
                 WHERE (wallet_recharge + wallet_withdraw + wallet_bonus) = 0 AND wallet > 0'
            );
        }
    }

    public function down()
    {
        $cols = ['kyc_observations', 'kyc_back', 'kyc_front', 'kyc_status', 'wallet_bonus', 'wallet_withdraw', 'wallet_recharge'];
        foreach ($cols as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
