<?php

require_once __DIR__ . '/../models/Notification.php';

class NotificationController
{
    private $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    public function getUserNotifications(int $userId): array
    {
        return $this->notificationModel->getByUserId($userId);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notificationModel->getUnreadCount($userId);
    }

    public function markAsRead(
        int $notificationId,
        int $userId
    ): bool {
        return $this->notificationModel->markAsRead(
            $notificationId,
            $userId
        );
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationModel->markAllAsRead($userId);
    }

    public function create(
        int $userId,
        string $title,
        string $message
    ): bool {
        return $this->notificationModel->create(
            $userId,
            $title,
            $message
        );
    }
}
?>
