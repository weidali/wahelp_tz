<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Database\Database;

$env = new Env(__DIR__ . '/../.env');
$host = $env->get('DB_HOST');
$driver = $env->get('DB_DRIVER');
$dbname = $env->get('DB_NAME');
$username = $env->get('DB_USER');
$password = $env->get('DB_PASSWORD');

try {
	$db = new Database($host, $driver, $dbname, $username, $password);
	$results = $db->get();

	foreach ($results as $row) {
		echo "ID: {$row['id']}, Name: {$row['name']}, Number: {$row['number']}" . PHP_EOL;
	}
} catch (Exception $e) {
	echo "Error: " . $e->getMessage();
}
