<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAffiliateGgrSystem extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('affiliate_ggr_events')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'player_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'game_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'event_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'reference_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'reference_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['game_id', 'player_id']);
            $this->forge->addKey(['player_id', 'event_type']);
            $this->forge->createTable('affiliate_ggr_events', true);
        }

        if (! $this->db->tableExists('affiliate_ggr_commissions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'player_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'affiliate_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'affiliate_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                ],
                'game_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'total_stake' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'total_payout' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'ggr_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'commission_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '8,4',
                    'default'    => '0.0000',
                ],
                'commission_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'payment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'period_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['player_id', 'affiliate_id', 'affiliate_type', 'game_id']);
            $this->forge->addKey(['affiliate_id', 'affiliate_type', 'status']);
            $this->forge->createTable('affiliate_ggr_commissions', true);
        }

        $settings = [
            'activateGgrAffiliate'      => '1',
            'affiliateCommissionMode'   => 'hybrid',
            'rateAffiliateCpa'          => '0',
            'autoApproveGgrCommissions' => '1',
        ];

        foreach ($settings as $key => $value) {
            if ($this->db->table('system')->where('key', $key)->countAllResults() === 0) {
                $this->db->table('system')->insert(['key' => $key, 'value' => $value]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('affiliate_ggr_commissions', true);
        $this->forge->dropTable('affiliate_ggr_events', true);

        foreach (['activateGgrAffiliate', 'affiliateCommissionMode', 'rateAffiliateCpa', 'autoApproveGgrCommissions'] as $key) {
            $this->db->table('system')->where('key', $key)->delete();
        }
    }
}
