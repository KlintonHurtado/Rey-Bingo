<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStoreToDeposits extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('deposits') && ! $this->db->fieldExists('store', 'deposits')) {
            $this->forge->addColumn('deposits', [
                'store' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'user',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('store', 'deposits')) {
            $this->forge->dropColumn('deposits', 'store');
        }
    }
}
