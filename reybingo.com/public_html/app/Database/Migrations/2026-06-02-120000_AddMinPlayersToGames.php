<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMinPlayersToGames extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('games') && ! $this->db->fieldExists('min_players', 'games')) {
            $this->forge->addColumn('games', [
                'min_players' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 10,
                    'null'       => false,
                    'after'      => 'price',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('min_players', 'games')) {
            $this->forge->dropColumn('games', 'min_players');
        }
    }
}
