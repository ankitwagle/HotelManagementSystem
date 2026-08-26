<?php

require_once __DIR__ . '/../config/database.php';

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                user_id,
                title,
                message,
                is_read,
                created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ?
              AND is_read = 0
        ");

        $stmt->execute([
            $userId
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function markAsRead(
        int $notificationId,
        int $userId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
        ");

        return $stmt->execute([
            $notificationId,
            $userId
        ]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        return $stmt->execute([
            $userId
        ]);
    }

    public function create(
        int $userId,
        string $title,
        string $message
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO notifications
            (
                user_id,
                title,
                message,
                is_read,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0,
                NOW()
            )
        ");

        return $stmt->execute([
            $userId,
            $title,
            $message
        ]);
    }
}
?>
