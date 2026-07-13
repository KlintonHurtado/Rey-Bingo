<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStoreCommissionSettings extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('system')) {
            $exists = $this->db->table('system')->where('key', 'rateStoreCommission')->countAllResults();
            if ($exists === 0) {
                $this->db->table('system')->insert([
                    'key'   => 'rateStoreCommission',
                    'value' => '0',
                ]);
            }
        }

        if ($this->db->tableExists('users') && ! $this->db->fieldExists('store_commission_rate', 'users')) {
            $this->forge->addColumn('users', [
                'store_commission_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '8,4',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'business_name',
                ],
            ]);
        }

        if ($this->db->tableExists('deposits') && ! $this->db->fieldExists('commission_amount', 'deposits')) {
            $this->forge->addColumn('deposits', [
                'commission_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'amount',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('system')) {
            $this->db->table('system')->where('key', 'rateStoreCommission')->delete();
        }

        if ($this->db->tableExists('users') && $this->db->fieldExists('store_commission_rate', 'users')) {
            $this->forge->dropColumn('users', 'store_commission_rate');
        }

        if ($this->db->tableExists('deposits') && $this->db->fieldExists('commission_amount', 'deposits')) {
            $this->forge->dropColumn('deposits', 'commission_amount');
        }
    }
}
