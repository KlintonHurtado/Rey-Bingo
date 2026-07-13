<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferredStoreIdToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users') || $this->db->fieldExists('referred_store_id', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'referred_store_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'referred_code',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('referred_store_id', 'users')) {
            $this->forge->dropColumn('users', 'referred_store_id');
        }
    }
}
