<?php

if (!isset($_SESSION['user']['id'])) {
    return;
}

require_once __DIR__ . '/../../controllers/NotificationController.php';

$headerNotificationController = new NotificationController();
$headerUnreadNotifications = $headerNotificationController->getUnreadCount(
    (int) $_SESSION['user']['id']
);

if ($headerUnreadNotifications > 0): ?>
    <span class="notification-badge">
        <?= $headerUnreadNotifications ?>
    </span>
<?php endif; ?>
