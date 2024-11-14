<?php

namespace App\Services;

use App\Database\Database;
use Exception;

class NotificationService
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	private function validateNotification(int $notificationId): void
	{
		$pdo = $this->database->getConnection();
		$stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = :id");
		$stmt->execute(['id' => $notificationId]);

		if (!$stmt->fetch()) {
			throw new Exception("Notification with ID $notificationId does not exist.");
		}
	}

	private function getPendingUsers(int $notificationId): \Generator
	{
		$pdo = $this->database->getConnection();

		$stmt = $pdo->prepare("
            SELECT u.id AS user_id
            FROM users u
            LEFT JOIN user_notifications un ON u.id = un.user_id AND un.notification_id = :notification_id
            WHERE un.sent IS NULL OR un.sent = FALSE
        ");
		$stmt->execute(['notification_id' => $notificationId]);

		while ($user = $stmt->fetch()) {
			yield $user;
		}
	}

	private function markAsSent(int $userId, int $notificationId): void
	{
		$pdo = $this->database->getConnection();
		$stmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, notification_id, sent, sent_at)
            VALUES (:user_id, :notification_id, TRUE, NOW())
            ON DUPLICATE KEY UPDATE sent = TRUE, sent_at = NOW()
        ");
		$stmt->execute(['user_id' => $userId, 'notification_id' => $notificationId]);
	}

	private function fakeNotification(int $userId, int $notificationId, string $timestamp, bool $success): void
	{
		$logDir = __DIR__ . '/../Logs';
		if (!file_exists($logDir)) {
			mkdir($logDir, 0777, true);
		}

		$logMessage = "User ID: $userId, Notification ID: $notificationId";


		$logFile = $logDir . ($success ? "successful_notifications_$timestamp.txt" : "failed_notifications_$timestamp.txt");

		file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
	}

	public function sendNotifications(int $notificationId): array
	{
		$this->validateNotification($notificationId);
		$users = $this->getPendingUsers($notificationId);

		if (empty($users)) {
			return [
				'success' => true,
				'message' => 'All users have already been notified.',
				'data' => [
					'notification_id' => $notificationId,
					'sent' => 0,
					'errors' => 0
				]
			];
		}

		$sentCount = 0;
		$errorCount = 0;
		$timestamp = date('Y-m-d_H-i-s');

		foreach ($users as $user) {
			$userId = $user['user_id'];

			$pdo = $this->database->getConnection();
			$pdo->beginTransaction();
			try {
				$this->fakeNotification($userId, $notificationId, $timestamp, true);
				$this->markAsSent($userId, $notificationId);

				$pdo->commit();
				$sentCount++;
			} catch (Exception $e) {
				$this->fakeNotification($userId, $notificationId, $timestamp, false);

				$pdo->rollBack();
				$errorCount++;
			}
		}

		return [
			'success' => true,
			'message' => 'Notification process completed.',
			'data' => [
				'notification_id' => $notificationId,
				'sent' => $sentCount,
				'errors' => $errorCount
			]
		];
	}
}
