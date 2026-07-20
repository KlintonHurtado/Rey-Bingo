<?php
require_once 'app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$fields = $db->getFieldData('games');
foreach($fields as $field) {
    echo $field->name . ' - ' . $field->type . "\n";
}
