<?php

namespace App\Database;

use PDO;
use PDOException;


class Database
{
	private PDO $pdo;

	/**
	 * Конструктор класса Database
	 * 
	 * @param string $host
	 * @param string $driver
	 * @param string $databasename
	 * @param string $username
	 * @param string $password
	 * 
	 * @throws PDOException Если соединение с базой данных не удалось
	 */
	public function __construct(string $host, string $driver, string $databasename, string $username, ?string $password)
	{
		$dsn = "$driver:host=$host;dbname=$databasename";

		try {
			$this->pdo = new PDO($dsn, $username, $password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			die("Connection failed: " . $e->getMessage());
		}


		$this->create();
		$this->addUniqueIndexForNumberField();
		$this->fill();
	}

	private function create(): void
	{
		$sql = "
		CREATE TABLE IF NOT EXISTS users (
			id INT AUTO_INCREMENT PRIMARY KEY,
			number VARCHAR(15) NOT NULL,
			name VARCHAR(255) NOT NULL
		);
		";

		$this->pdo->exec($sql);
	}

	private function addUniqueIndexForNumberField(): void
	{
		$indexCheckSQL = "
            SELECT COUNT(1) AS count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'number'
              AND INDEX_NAME = 'unique_number';
        ";

		$stmt = $this->pdo->query($indexCheckSQL);
		$indexExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

		if (!$indexExists) {
			$addIndexSQL = "CREATE UNIQUE INDEX unique_number ON users (number);";
			$this->pdo->exec($addIndexSQL);
		}
	}

	private function fill(): void
	{
		$stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		$recordCount = $row['COUNT(*)'];
		if ($recordCount == 0) {
			$stmt = $this->pdo->prepare("INSERT INTO users (number, name) VALUES (?, ?)");

			$data = [
				[978978978, 'Micke Jack'],
				['555555555', 'Test User'],
			];

			foreach ($data as $item) {
				try {
					$stmt->execute($item);
				} catch (PDOException $e) {
					echo "Ошибка вставки данных: " . $e->getMessage() . "\n";
				}
			}
		}
	}

	public function getConnection(): PDO
	{
		return $this->pdo;
	}

	public function get(): array
	{
		$sql = "SELECT * FROM users";
		$stmt = $this->pdo->query($sql);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
