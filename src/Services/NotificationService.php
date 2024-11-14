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

	private function fakeNotification(int $userId, int $notificationId): void
	{
		echo "Notification $notificationId sent to user $userId\n";
	}

	public function sendNotifications(int $notificationId): void
	{
		$pdo = $this->database->getConnection();

		$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = :id");
		$stmt->execute(['id' => $notificationId]);
		$notification = $stmt->fetch();

		if (!$notification) {
			throw new Exception("Notification with ID $notificationId does not exist.");
		}

		$stmt = $pdo->prepare("
            SELECT u.id AS user_id
            FROM users u
            LEFT JOIN user_notifications un ON u.id = un.user_id AND un.notification_id = :notification_id
            WHERE un.sent IS NULL OR un.sent = FALSE
        ");
		$stmt->execute(['notification_id' => $notificationId]);

		$users = $stmt->fetchAll();

		if (empty($users)) {
			echo "All users have already been notified.\n";
			return;
		}

		foreach ($users as $user) {
			$userId = $user['user_id'];

			try {
				$pdo->beginTransaction();

				$this->fakeNotification($userId, $notificationId);

				$stmt = $pdo->prepare("
                    INSERT INTO user_notifications (user_id, notification_id, sent, sent_at)
                    VALUES (:user_id, :notification_id, TRUE, NOW())
                    ON DUPLICATE KEY UPDATE sent = TRUE, sent_at = NOW()
                ");
				$stmt->execute(['user_id' => $userId, 'notification_id' => $notificationId]);

				$pdo->commit();
			} catch (Exception $e) {
				$pdo->rollBack();
				echo "Failed to send notification to user $userId: " . $e->getMessage() . "\n";
			}
		}
	}
}
