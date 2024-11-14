<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Utils\ApiResponse;


try {
	$router = new Router();
	require_once __DIR__ . '/../src/routes.php';

	$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
} catch (Exception $e) {
	ApiResponse::json([
		'success' => false,
		'message' => $e->getMessage(),
	], 500);
}
