<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando de auditoría física y validación de producción.
 * Ejecución:
 *   php spark bingo:audit-prod
 *   php spark bingo:audit-prod --fix
 */
class AuditProd extends BaseCommand
{
    protected $group       = 'Bingo';
    protected $name        = 'bingo:audit-prod';
    protected $description = 'Audita físicamente índices MySQL, explain plans, motores de tabla y OPcache';
    protected $usage       = 'bingo:audit-prod [--fix]';
    protected $options     = [
        '--fix' => 'Crea automáticamente los índices faltantes si alguno no existe',
    ];

    public function run(array $params)
    {
        $fix = array_key_exists('fix', $params)
            || CLI::getOption('fix') !== null
            || in_array('--fix', $_SERVER['argv'] ?? [], true);

        CLI::write("================================================================", 'cyan');
        CLI::write(" AUDITORÍA TÉCNICA Y VALIDACIÓN DE PRODUCCIÓN - REY BINGO", 'yellow');
        CLI::write("================================================================", 'cyan');

        $db = \Config\Database::connect();

        // 1. MOTORES DE TABLA (InnoDB vs MyISAM)
        CLI::write("\n[1] VERIFICACIÓN DE MOTOR DE ALMACENAMIENTO (InnoDB = Row Lock)", 'green');
        $tables = ['numbers', 'cartons', 'boards', 'games', 'sings', 'messages', 'users'];
        $dbName = $db->getDatabase();

        foreach ($tables as $tbl) {
            $row = $db->query("SELECT ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH 
                               FROM information_schema.TABLES 
                               WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$dbName, $tbl])->getRowArray();
            if ($row) {
                $engine = $row['ENGINE'] ?? 'UNKNOWN';
                $engineColor = ($engine === 'InnoDB') ? 'green' : 'red';
                CLI::write(sprintf("  %-12s: Motor = %-8s | Filas aprox: %-8s | Tamaño: %s KB", 
                    $tbl, $engine, number_format((int)$row['TABLE_ROWS']), round(((int)$row['DATA_LENGTH'] + (int)$row['INDEX_LENGTH']) / 1024, 1)
                ), $engineColor);
            }
        }

        // 2. ÍNDICES REQUERIDOS
        CLI::write("\n[2] COMPROBACIÓN FÍSICA DE ÍNDICES EN MYSQL", 'green');
        $requiredIndexes = [
            'numbers'  => [
                'idx_numbers_carton_status' => ['carton', 'status'],
                'idx_numbers_number'        => ['number'],
            ],
            'cartons'  => [
                'idx_cartons_game_user'     => ['game', 'user'],
                'idx_cartons_user'          => ['user'],
            ],
            'boards'   => [
                'idx_boards_game_number'    => ['game', 'number'],
                'idx_boards_game_created'   => ['game', 'created_at'],
            ],
            'games'    => [
                'idx_games_type_status'     => ['type', 'status'],
                'idx_games_date_status'     => ['date', 'status'],
            ],
            'sings'    => [
                'idx_sings_game_status'     => ['game', 'status'],
                'idx_sings_game_modality'   => ['game', 'modality'],
            ],
            'messages' => [
                'idx_messages_game_status_id' => ['game', 'status', 'id'],
            ],
        ];

        $missing = [];

        foreach ($requiredIndexes as $table => $indexes) {
            if (!$db->tableExists($table)) {
                CLI::write("  Tabla `{$table}` no existe.", 'red');
                continue;
            }

            $existing = [];
            $rows = $db->query("SHOW INDEX FROM `{$table}`")->getResultArray();
            foreach ($rows as $r) {
                $existing[$r['Key_name']] = true;
            }

            foreach ($indexes as $name => $cols) {
                $status = isset($existing[$name]) ? 'EXISTE' : 'FALTANTE';
                $color  = isset($existing[$name]) ? 'green' : 'red';
                CLI::write(sprintf("  `%-8s` . `%-28s` (%s) -> [%s]", $table, $name, implode(',', $cols), $status), $color);

                if (!isset($existing[$name])) {
                    $missing[] = ['table' => $table, 'name' => $name, 'cols' => $cols];
                    if ($fix) {
                        $colList = implode('`, `', $cols);
                        $db->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$colList}`)");
                        CLI::write("    -> Creado exitosamente: `{$name}`", 'yellow');
                    }
                }
            }
        }

        if (empty($missing)) {
            CLI::write("\n  Todos los índices requeridos existen físicamente en MySQL.", 'green');
        } elseif (!$fix) {
            CLI::write("\n  Existen " . count($missing) . " índices faltantes. Ejecuta con --fix para crearlos.", 'yellow');
        }

        // 3. EXPLAIN PLANS EN CONSULTAS CRÍTICAS
        CLI::write("\n[3] EXPLAIN PLANS EN CONSULTAS CRÍTICAS (Verificación de Key y Rows)", 'green');
        $queries = [
            'numberGet: Última balota cantada' => "EXPLAIN SELECT * FROM boards WHERE game = 1 ORDER BY created_at DESC, id DESC LIMIT 1",
            'numberGet: Verificación de números de cartón' => "EXPLAIN SELECT * FROM numbers WHERE carton = 1 AND status = 1",
            'numberGet: Conteo de bingos por juego' => "EXPLAIN SELECT modality FROM sings WHERE game = 1 GROUP BY modality",
            'dialNumber: Búsqueda de cartón por usuario' => "EXPLAIN SELECT * FROM cartons WHERE game = 1 AND user = 1",
        ];

        foreach ($queries as $label => $sql) {
            CLI::write("\n  {$label}:", 'yellow');
            try {
                $explainRows = $db->query($sql)->getResultArray();
                foreach ($explainRows as $row) {
                    $keyUsed = $row['key'] ?? 'NULL';
                    $type    = $row['type'] ?? 'ALL';
                    $rows    = $row['rows'] ?? '?';
                    $extra   = $row['Extra'] ?? '';
                    $color   = ($type === 'ALL' || $keyUsed === 'NULL') ? 'red' : 'green';

                    CLI::write(sprintf("    type=%-8s | key=%-26s | rows=%-6s | Extra=%s", 
                        $type, $keyUsed, $rows, $extra
                    ), $color);
                }
            } catch (\Throwable $e) {
                CLI::write("    Error al ejecutar EXPLAIN: " . $e->getMessage(), 'red');
            }
        }

        // 4. OPCACHE DIAGNOSTICS
        CLI::write("\n[4] ESTADO DE OPCACHE Y TIEMPO DE RESPUESTA", 'green');
        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if ($status && is_array($status)) {
                $enabled = $status['opcache_enabled'] ? 'HABILITADO' : 'DESHABILITADO';
                $memUsed = round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2);
                $memFree = round(($status['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 2);
                $hitRate = round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 2);

                CLI::write("  OPcache: {$enabled}");
                CLI::write("  Memoria Usada: {$memUsed} MB | Libre: {$memFree} MB");
                CLI::write("  Hit Rate: {$hitRate}%", ($hitRate > 90 ? 'green' : 'yellow'));
                CLI::write("  validate_timestamps: " . ini_get('opcache.validate_timestamps'));
                CLI::write("  revalidate_freq: " . ini_get('opcache.revalidate_freq') . " s");
            } else {
                CLI::write("  OPcache no está activo en este entorno CLI.", 'yellow');
            }
        } else {
            CLI::write("  Extensión OPcache no instalada en este PHP CLI.", 'yellow');
        }

        // 5. MEMORIA Y SISTEMA
        CLI::write("\n[5] CONSUMO DE MEMORIA DEL PROCESO ACTUAL", 'green');
        $memUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $memPeak  = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        CLI::write("  Memoria en uso: {$memUsage} MB | Pico: {$memPeak} MB | Límite: " . ini_get('memory_limit'));

        CLI::write("\n================================================================", 'cyan');
        CLI::write(" AUDITORÍA COMPLETADA", 'yellow');
        CLI::write("================================================================\n", 'cyan');
    }
}
