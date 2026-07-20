<?php
require_once 'app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$forge = \Config\Database::forge();

// Crear tabla firebase_tokens
if (!$db->tableExists('firebase_tokens')) {
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
        'device' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
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
    $forge->createTable('firebase_tokens');
    echo "Tabla firebase_tokens creada exitosamente.\n";
} else {
    echo "La tabla firebase_tokens ya existe.\n";
}
