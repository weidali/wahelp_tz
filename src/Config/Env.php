<?php

namespace App\Config;


class Env
{
	private array $data;

	public function __construct(string $filePath)
	{
		if (!file_exists($filePath)) {
			throw new \Exception(".env file not found: $filePath");
		}

		$this->data = parse_ini_file($filePath);
	}

	public function get(string $key, $default = null)
	{
		return $this->data[$key] ?? $default;
	}
}
