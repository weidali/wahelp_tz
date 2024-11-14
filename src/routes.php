<?php

use App\Config\Env;
use App\Database\Database;
use App\Utils\ApiResponse;

$env = new Env(__DIR__ . '/../.env');
$host = $env->get('DB_HOST');
$driver = $env->get('DB_DRIVER');
$dbname = $env->get('DB_NAME');
$username = $env->get('DB_USER');
$password = $env->get('DB_PASSWORD');

$db = new Database($host, $driver, $dbname, $username, $password);

$router->add('GET', '/', function () use ($db) {
	$results = $db->get();

	ApiResponse::json($results);
});
