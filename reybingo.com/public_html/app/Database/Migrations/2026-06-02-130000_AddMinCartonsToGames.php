<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMinCartonsToGames extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('games') && ! $this->db->fieldExists('min_cartons', 'games')) {
            $this->forge->addColumn('games', [
                'min_cartons' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 10,
                    'null'       => false,
                    'after'      => 'min_players',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('min_cartons', 'games')) {
            $this->forge->dropColumn('games', 'min_cartons');
        }
    }
}
