<?php

namespace App;

use App\Utils\ApiResponse;

class Router
{
	private array $routes = [];

	public function add(string $method, string $path, callable $handler): void
	{
		$this->routes[] = compact('method', 'path', 'handler');
	}

	public function dispatch(string $uri, string $method): void
	{
		foreach ($this->routes as $route) {
			if ($route['method'] === strtoupper($method) && $route['path'] === $uri) {
				call_user_func($route['handler']);
				return;
			}
		}

		ApiResponse::json([
			'success' => false,
			'message' => 'Route not found'
		], 404);
		exit;
	}
}
