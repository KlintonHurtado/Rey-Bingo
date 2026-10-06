<?php
/**
 * Diagnóstico y Auditoría Física de Producción - Rey Bingo
 * Acceso:
 *   CLI: php diagnostics_prod.php [--fix]
 *   Web: https://bingo.reybingo.com/tools/diagnostics_prod.php?token=reybingo_cron_secret_key_2026[&fix=1]
 */

define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require_once FCPATH . '../app/Config/Constants.php';

$isCli = (php_sapi_name() === 'cli');

// Verificación de seguridad
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if (!$isCli && $token !== 'reybingo_cron_secret_key_2026') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acceso denegado. Token inválido o ausente.']);
    exit;
}

$fix = false;
if ($isCli) {
    $fix = in_array('--fix', $argv ?? []);
} else {
    $fix = isset($_GET['fix']) && $_GET['fix'] == '1';
    header('Content-Type: text/plain; charset=utf-8');
}

// Cargar configuración de base de datos desde .env de CI4
$envFile = FCPATH . '../.env';
$dbConfig = [
    'hostname' => 'localhost',
    'database' => '',
    'username' => '',
    'password' => '',
    'port'     => 3306,
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, 'database.default.hostname') !== false) {
            $parts = explode('=', $line, 2);
            $dbConfig['hostname'] = trim($parts[1] ?? 'localhost');
        } elseif (strpos($line, 'database.default.database') !== false) {
            $parts = explode('=', $line, 2);
            $dbConfig['database'] = trim($parts[1] ?? '');
        } elseif (strpos($line, 'database.default.username') !== false) {
            $parts = explode('=', $line, 2);
            $dbConfig['username'] = trim($parts[1] ?? '');
        } elseif (strpos($line, 'database.default.password') !== false) {
            $parts = explode('=', $line, 2);
            $dbConfig['password'] = trim($parts[1] ?? '');
        } elseif (strpos($line, 'database.default.port') !== false) {
            $parts = explode('=', $line, 2);
            $dbConfig['port'] = (int) trim($parts[1] ?? 3306);
        }
    }
}

echo "=================================================================\n";
echo " REY BINGO - DIAGNÓSTICO Y VALIDACIÓN DE PRODUCCIÓN\n";
echo " Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "=================================================================\n\n";

// Conexión directa mysqli
$mysqli = @new mysqli($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database'], $dbConfig['port']);

if ($mysqli->connect_error) {
    echo "[ERROR CRÍTICO] No se pudo conectar a MySQL: " . $mysqli->connect_error . "\n";
    echo "Host: {$dbConfig['hostname']} | DB: {$dbConfig['database']} | User: {$dbConfig['username']}\n";
    exit(1);
}

echo "[1] MOTORES DE ALMACENAMIENTO (InnoDB = Row Lock)\n";
$tables = ['numbers', 'cartons', 'boards', 'games', 'sings', 'messages', 'users'];
foreach ($tables as $tbl) {
    $res = $mysqli->query("SELECT ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$dbConfig['database']}' AND TABLE_NAME = '{$tbl}'");
    if ($res && $row = $res->fetch_assoc()) {
        $engine = $row['ENGINE'];
        $sizeKb = round(((int)$row['DATA_LENGTH'] + (int)$row['INDEX_LENGTH']) / 1024, 1);
        echo sprintf("  %-10s : %-8s | Filas aprox: %-7s | Tamaño: %s KB\n", $tbl, $engine, number_format((int)$row['TABLE_ROWS']), $sizeKb);
    }
}

echo "\n[2] VERIFICACIÓN FÍSICA DE ÍNDICES EN TABLAS CRÍTICAS\n";
$required = [
    'numbers'  => ['idx_numbers_carton_status' => ['carton', 'status'], 'idx_numbers_number' => ['number']],
    'cartons'  => ['idx_cartons_game_user' => ['game', 'user'], 'idx_cartons_user' => ['user']],
    'boards'   => ['idx_boards_game_number' => ['game', 'number'], 'idx_boards_game_created' => ['game', 'created_at']],
    'games'    => ['idx_games_type_status' => ['type', 'status'], 'idx_games_date_status' => ['date', 'status']],
    'sings'    => ['idx_sings_game_status' => ['game', 'status'], 'idx_sings_game_modality' => ['game', 'modality']],
    'messages' => ['idx_messages_game_status_id' => ['game', 'status', 'id']],
];

$missingCount = 0;
foreach ($required as $tbl => $indexes) {
    $existing = [];
    $res = $mysqli->query("SHOW INDEX FROM `{$tbl}`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $existing[$r['Key_name']] = true;
        }
    }

    foreach ($indexes as $idxName => $cols) {
        $found = isset($existing[$idxName]);
        $status = $found ? 'EXISTE' : 'FALTANTE';
        echo sprintf("  `%-8s` . `%-28s` -> [%s]\n", $tbl, $idxName, $status);
        if (!$found) {
            $missingCount++;
            if ($fix) {
                $colStr = implode('`, `', $cols);
                if ($mysqli->query("ALTER TABLE `{$tbl}` ADD INDEX `{$idxName}` (`{$colStr}`)")) {
                    echo "    -> [CREADO EXITOSAMENTE]\n";
                } else {
                    echo "    -> [ERROR AL CREAR]: " . $mysqli->error . "\n";
                }
            }
        }
    }
}

if ($missingCount === 0) {
    echo "  >> Todos los índices requeridos existen físicamente en la BD.\n";
} elseif (!$fix) {
    echo "  >> Existen {$missingCount} índices faltantes. Ejecuta con --fix o ?fix=1 para crearlos de inmediato.\n";
}

echo "\n[3] EXPLAIN PLANS EN CONSULTAS CRÍTICAS\n";
$queries = [
    'Última balota (boards)'     => "EXPLAIN SELECT * FROM boards WHERE game = 1 ORDER BY created_at DESC, id DESC LIMIT 1",
    'Números cartón (numbers)'   => "EXPLAIN SELECT * FROM numbers WHERE carton = 1 AND status = 1",
    'Conteo bingos (sings)'      => "EXPLAIN SELECT modality FROM sings WHERE game = 1 GROUP BY modality",
    'Cartón de usuario (cartons)' => "EXPLAIN SELECT * FROM cartons WHERE game = 1 AND user = 1",
];

foreach ($queries as $label => $q) {
    echo "  * {$label}:\n";
    $res = $mysqli->query($q);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo sprintf("    type=%-7s | key=%-26s | rows=%-6s | Extra=%s\n", 
                $row['type'] ?? '', $row['key'] ?? 'NULL', $row['rows'] ?? '', $row['Extra'] ?? ''
            );
        }
    } else {
        echo "    Error: " . $mysqli->error . "\n";
    }
}

echo "\n[4] ESTADO DE OPCACHE\n";
if (function_exists('opcache_get_status')) {
    $op = @opcache_get_status(false);
    if ($op && is_array($op)) {
        $used = round(($op['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 1);
        $free = round(($op['memory_usage']['free_memory'] ?? 0) / 1024 / 1024, 1);
        $hit  = round($op['opcache_statistics']['opcache_hit_rate'] ?? 0, 1);
        echo "  Activo: Sí | Usado: {$used} MB | Libre: {$free} MB | Hit Rate: {$hit}%\n";
        echo "  validate_timestamps: " . ini_get('opcache.validate_timestamps') . "\n";
        echo "  revalidate_freq: " . ini_get('opcache.revalidate_freq') . " s\n";
    } else {
        echo "  OPcache no reporta estado activo.\n";
    }
} else {
    echo "  Extensión OPcache no instalada en este entorno.\n";
}

echo "\n[5] MEMORIA Y LÍMITES PHP\n";
echo "  Memoria actual: " . round(memory_get_usage(true)/1024/1024, 2) . " MB\n";
echo "  Pico de memoria: " . round(memory_get_peak_usage(true)/1024/1024, 2) . " MB\n";
echo "  memory_limit: " . ini_get('memory_limit') . "\n";
echo "  max_execution_time: " . ini_get('max_execution_time') . " s\n";

echo "\n=================================================================\n";
echo " DIAGNÓSTICO FINALIZADO CON ÉXITO\n";
echo "=================================================================\n";
