<?php
require_once 'app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$cron = new \App\Controllers\Cron();
$response = $cron->runAutoAddGames();
echo $response->getBody();
