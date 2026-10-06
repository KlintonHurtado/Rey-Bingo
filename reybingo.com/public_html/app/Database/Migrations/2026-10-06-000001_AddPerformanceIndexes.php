<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migración para agregar índices de alto rendimiento a tablas críticas
 * (numbers, cartons, boards, games, sings, messages) para eliminar
 * bloqueos de CPU y cuellos de botella 502/504.
 */
class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        helper('bingo');
        if (function_exists('bingo_ensure_performance_indexes')) {
            bingo_ensure_performance_indexes();
        }
    }

    public function down()
    {
        $db = $this->db;
        $dropMap = [
            'numbers' => ['idx_numbers_carton_status', 'idx_numbers_number'],
            'cartons' => ['idx_cartons_game_user', 'idx_cartons_user'],
            'boards'  => ['idx_boards_game_number', 'idx_boards_game_created'],
            'games'   => ['idx_games_type_status', 'idx_games_date_status'],
            'sings'   => ['idx_sings_game_status', 'idx_sings_game_modality'],
            'messages'=> ['idx_messages_game_status_id'],
        ];

        foreach ($dropMap as $table => $indexes) {
            if (!$db->tableExists($table)) {
                continue;
            }
            $existing = [];
            $rows = $db->query("SHOW INDEX FROM `{$table}`")->getResultArray();
            foreach ($rows as $r) {
                $existing[$r['Key_name']] = true;
            }
            foreach ($indexes as $idx) {
                if (isset($existing[$idx])) {
                    $db->query("ALTER TABLE `{$table}` DROP INDEX `{$idx}`");
                }
            }
        }
    }
}
