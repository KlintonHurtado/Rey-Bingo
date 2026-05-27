<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UserAddressAndReseller extends Migration
{
    public function up()
    {
        $fields = [
            'address_line' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'email',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'address_line',
            ],
            'state' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'city',
            ],
            'is_reseller' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'state',
            ],
        ];

        foreach ($fields as $name => $def) {
            if ($this->db->tableExists('users') && ! $this->db->fieldExists($name, 'users')) {
                $this->forge->addColumn('users', [$name => $def]);
            }
        }
    }

    public function down()
    {
        foreach (['is_reseller', 'state', 'city', 'address_line'] as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
