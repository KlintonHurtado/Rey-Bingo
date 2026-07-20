<?php
require_once 'app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$forge = \Config\Database::forge();

// Crear tabla fcm_tokens
if (!$db->tableExists('fcm_tokens')) {
    $forge->addField([
        'id' => [
            'type'           => 'INT',
            'constraint'     => 11,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'user_id' => [
            'type'           => 'INT',
            'constraint'     => 11,
            'unsigned'       => true,
        ],
        'token' => [
            'type'       => 'TEXT',
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'updated_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
    ]);
    $forge->addKey('id', true);
    $forge->addKey('user_id');
    $forge->createTable('fcm_tokens');
    echo "Tabla fcm_tokens creada exitosamente.\n";
} else {
    echo "La tabla fcm_tokens ya existe.\n";
}

