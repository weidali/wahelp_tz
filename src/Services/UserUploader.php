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
			yield array_map('trim', $row);
		}

		fclose($handle);
	}

	private function userExists(string $number): bool
	{
		$stmt = $this->database->getConnection()->prepare("SELECT COUNT(*) FROM users WHERE number = :number");
		$stmt->execute(['number' => $number]);
		return $stmt->fetchColumn() > 0;
	}

	private function insertUser(string $number, string $name): bool
	{
		try {
			$stmt = $this->database->getConnection()->prepare("INSERT INTO users (number, name) VALUES (:number, :name)");
			$stmt->execute([':number' => $number, ':name' => $name]);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function uploadFromCsv(string $filePath): array
	{
		$pdo = $this->database->getConnection();
		$inserted = 0;
		$exists = 0;
		$errors = 0;

		$pdo->beginTransaction();
		try {
			foreach ($this->readCsv($filePath) as $row) {
				[$number, $name] = $row;

				if ($this->userExists($number)) {
					$exists++;
					continue;
				}

				if ($this->insertUser($number, $name)) {
					$inserted++;
				} else {
					$errors++;
				}
			}

			$pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
			throw new Exception("Transaction failed: " . $e->getMessage());
		}

		return [
			'inserted' => $inserted,
			'exists' => $exists,
			'errors' => $errors
		];
	}
}
