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

	private function markAsSent(array $users, int $notificationId): void
	{
		$pdo = $this->database->getConnection();

		$placeholders = implode(',', array_fill(0, count($users), '(?, ?, TRUE, NOW())'));
		$sql = "
            INSERT INTO user_notifications (user_id, notification_id, sent, sent_at)
            VALUES $placeholders
            ON DUPLICATE KEY UPDATE sent = TRUE, sent_at = NOW()
        ";

		$params = [];
		foreach ($users as $user) {
			$params[] = $user['user_id'];
			$params[] = $notificationId;
		}

		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
	}

	private function fakeNotification(int $userId, int $notificationId, string $timestamp, bool $success): string
	{
		$hash = hash('md5', rand(600, 6000));
		return "$hash - User ID: $userId, Notification ID: $notificationId, " . ($success ? 'Success' : 'Failed') . "\n";
	}

	public function sendNotifications(int $notificationId): array
	{
		$this->validateNotification($notificationId);

		$timestamp = date('Y-m-d_H-i-s');
		$logDir = __DIR__ . '/../Logs';
		if (!file_exists($logDir)) {
			mkdir($logDir, 0744, true);
		}

		$successfulNotifications = [];
		$failedNotifications = [];
		$usersBatch = [];

		foreach ($this->getPendingUsers($notificationId) as $user) {
			$usersBatch[] = $user;
			if (count($usersBatch) >= 100) {
				$this->markAsSent($usersBatch, $notificationId);
				foreach ($usersBatch as $user) {
					$successfulNotifications[] = $this->fakeNotification($user['user_id'], $notificationId, $timestamp, true);
				}
				$usersBatch = [];
			}
		}

		if (count($usersBatch) > 0) {
			$this->markAsSent($usersBatch, $notificationId);
			foreach ($usersBatch as $user) {
				$successfulNotifications[] = $this->fakeNotification($user['user_id'], $notificationId, $timestamp, true);
			}
		}

		if (!empty($successfulNotifications)) {
			$logFile = $logDir . "/successful_notifications_$timestamp.txt";
			file_put_contents($logFile, implode('', $successfulNotifications), FILE_APPEND);
		}

		if (!empty($failedNotifications)) {
			$logFile = $logDir . "/failed_notifications_$timestamp.txt";
			file_put_contents($logFile, implode('', $failedNotifications), FILE_APPEND);
		}

		return [
			'success' => true,
			'message' => 'Notification process completed.',
			'data' => [
				'notification_id' => $notificationId,
				'sent' => count($successfulNotifications),
				'errors' => count($failedNotifications)
			]
		];
	}
}
