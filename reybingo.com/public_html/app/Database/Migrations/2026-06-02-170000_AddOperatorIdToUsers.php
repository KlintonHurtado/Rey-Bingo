<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperatorIdToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users') || $this->db->fieldExists('operator_id', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'operator_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'referred_store_id',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('operator_id', 'users')) {
            $this->forge->dropColumn('users', 'operator_id');
        }
    }
}
