<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\App;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = new App();
$app->run();