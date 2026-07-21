<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();
$games = $db->query("SELECT id, description, type, status FROM games WHERE status = 2 OR status = 1 ORDER BY id DESC LIMIT 5")->getResultArray();

foreach ($games as $game) {
    $gameId = (int)$game['id'];
    echo "Game ID: $gameId (" . $game['description'] . ")\n";
    
    $query = $db->query("SELECT COUNT(DISTINCT user) as total FROM cartons WHERE game = ? AND user != 0", [$gameId]);
    $row   = $query->getRow();
    echo "  Cartons players: " . ($row ? $row->total : 0) . "\n";
    
    $query2 = $db->query("SELECT COUNT(DISTINCT user) as total FROM temp_cartons WHERE game = ?", [$gameId]);
    $row2   = $query2->getRow();
    echo "  Temp Cartons players: " . ($row2 ? $row2->total : 0) . "\n";
    
    $query3 = $db->query("SELECT COUNT(*) as total FROM cartons WHERE game = ? AND user != 0", [$gameId]);
    $row3   = $query3->getRow();
    echo "  Cartons count: " . ($row3 ? $row3->total : 0) . "\n";
    
    $query4 = $db->query("SELECT * FROM cartons WHERE game = ? LIMIT 2", [$gameId]);
    echo "  Sample carton: " . json_encode($query4->getResultArray()) . "\n";
}
