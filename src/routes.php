<?php

use App\Config\Env;
use App\Database\Database;
use App\Services\UserUploader;
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

$router->add('POST', '/upload', function () use ($db) {
	if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
		ApiResponse::json(['success' => false, 'message' => 'File upload failed', 'error_code' => $_FILES['file']['error']], 400);
	}

	$filePath = __DIR__ . '/../uploads/' . basename($_FILES['file']['name']);
	if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
		ApiResponse::json(['success' => false, 'message' => 'Failed to save file'], 500);
	}

	$uploader = new UserUploader($db);
	$result = $uploader->uploadFromCsv($filePath);

	ApiResponse::json($result);
	$result = $db->get();

	ApiResponse::json([
		'success' => true,
		'message' => 'File processed successfully.',
		'result' => $result,
	]);
});
