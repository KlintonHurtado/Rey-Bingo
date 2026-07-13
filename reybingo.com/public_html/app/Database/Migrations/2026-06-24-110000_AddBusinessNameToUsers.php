<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBusinessNameToUsers extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users') && ! $this->db->fieldExists('business_name', 'users')) {
            $this->forge->addColumn('users', [
                'business_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'lastname',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('business_name', 'users')) {
            $this->forge->dropColumn('users', 'business_name');
        }
    }
}
