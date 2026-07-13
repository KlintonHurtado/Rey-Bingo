<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAffiliateGgrMonthlySettlements extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('affiliate_ggr_monthly_settlements')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
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
                'period_month' => [
                    'type' => 'DATE',
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
                'total_ggr' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'commission_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'commission_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'payment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 2,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['affiliate_id', 'affiliate_type', 'period_month']);
            $this->forge->createTable('affiliate_ggr_monthly_settlements', true);
        }

        if ($this->db->tableExists('system')
            && $this->db->table('system')->where('key', 'ggrSettlementMode')->countAllResults() === 0) {
            $this->db->table('system')->insert(['key' => 'ggrSettlementMode', 'value' => 'monthly']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('affiliate_ggr_monthly_settlements', true);
        $this->db->table('system')->where('key', 'ggrSettlementMode')->delete();
    }
}
