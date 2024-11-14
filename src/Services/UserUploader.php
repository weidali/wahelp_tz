<?php

namespace App\Services;

use App\Database\Database;
use Exception;


class UserUploader
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	private function readCsv(string $filePath): \Generator
	{
		if (!file_exists($filePath)) {
			throw new Exception("CSV file not found: $filePath");
		}

		$handle = fopen($filePath, 'r');
		if ($handle === false) {
			throw new Exception("Failed to open file: $filePath");
		}

		fgetcsv($handle);

		while (($row = fgetcsv($handle)) !== false) {
			$row = array_map('trim', $row);
			yield $row;
		}

		fclose($handle);
	}

	public function uploadFromCsv(string $filePath): array
	{
		$pdo = $this->database->getConnection();
		$inserted = 0;
		$exists = 0;
		$errors = 0;

		$this->database->getConnection()->beginTransaction();

		foreach ($this->readCsv($filePath) as $row) {
			$number = $row[0];
			$name = $row[1];

			$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE number = :number");
			$stmt->execute(['number' => $number]);
			$columns = $stmt->fetchColumn() > 0;
			if ($columns) {
				$exists++;
				continue;
			}

			try {
				$stmt = $pdo->prepare("INSERT INTO users (number, name) VALUES (:number, :name)");
				$stmt->execute([
					':number' => $number,
					':name' => $name
				]);
				$inserted++;
			} catch (Exception $e) {
				$errors++;
			}
		}
		$this->database->getConnection()->commit();

		return ['inserted' => $inserted, 'exists' => $exists, 'errors' => $errors];
	}
}
