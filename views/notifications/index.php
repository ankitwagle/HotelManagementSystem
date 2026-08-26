<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$notificationController =
    new NotificationController();

/*
 * Mark notification as read.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_read'])
) {
    $notificationId =
        (int) ($_POST['notification_id'] ?? 0);

    if ($notificationId > 0) {
        $notificationController->markAsRead(
            $notificationId,
            $userId
        );
    }

    header('Location: /views/notifications/index.php');
    exit;
}

/*
 * Mark all notifications as read.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['mark_all_read'])
) {
    $notificationController->markAllAsRead(
        $userId
    );

    header('Location: /views/notifications/index.php');
    exit;
}

/*
 * Get notifications.
 */
$notifications =
    $notificationController->getUserNotifications(
        $userId
    );

$unreadCount =
    $notificationController->getUnreadCount(
        $userId
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Notifications | LuxeStay
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .notifications-page {
        max-width: 950px;
        margin: 50px auto;
        padding: 20px;
    }

    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .notifications-header h1 {
        margin-bottom: 5px;
        color: #102a4c;
    }

    .unread-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border-radius: 20px;
        background: #b08d57;
        color: white;
        font-weight: 700;
    }

    .mark-all-button {
        border: none;
        background: #102a4c;
        color: white;
        padding: 11px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
    }

    .mark-all-button:hover {
        opacity: .9;
    }

    .notification-list {
        display: grid;
        gap: 15px;
    }

    .notification-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .06);
    }

    .notification-card.unread {
        border-left: 5px solid #b08d57;
        background: #fffdf8;
    }

    .notification-top {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: flex-start;
    }

    .notification-title {
        margin: 0;
        color: #102a4c;
        font-size: 19px;
    }

    .notification-message {
        margin: 10px 0;
        color: #475569;
        line-height: 1.6;
    }

    .notification-date {
        color: #94a3b8;
        font-size: 13px;
    }

    .read-button {
        margin-top: 10px;
        border: 1px solid #102a4c;
        background: white;
        color: #102a4c;
        padding: 8px 13px;
        border-radius: 7px;
        cursor: pointer;
        font-weight: 600;
    }

    .read-button:hover {
        background: #102a4c;
        color: white;
    }

    .read-label {
        color: #16a34a;
        font-size: 13px;
        font-weight: 700;
    }

    .empty-notifications {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 45px 25px;
        text-align: center;
        color: #64748b;
    }

    .back-link {
        display: inline-block;
        margin-top: 25px;
        color: #9b7418;
        font-weight: 700;
        text-decoration: none;
    }

    @media (max-width: 650px) {

        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .notification-top {
            flex-direction: column;
        }

    }

</style>
```

</head>

<body>

<header>

```
<div class="logo">
    🛏 LuxeStay
</div>

<nav>

    <a href="/index.php">
        Home
    </a>

    <a href="/views/rooms/index.php">
        Rooms
    </a>

    <a href="/views/bookings/index.php">
        My Reservations
    </a>

    <a href="/views/payments/index.php">
        Payments
    </a>

    <a href="/views/notifications/index.php">
        Notifications
        <?php if ($unreadCount > 0): ?>
            (<?= $unreadCount ?>)
        <?php endif; ?>
    </a>

    <a href="/views/users/profile.php">
        Profile
    </a>

    <a href="/logout.php">
        Logout
    </a>

</nav>
```

</header>

<main class="notifications-page">

```
<div class="notifications-header">

    <div>

        <p class="eyebrow">
            LUXESTAY
        </p>

        <h1>
            Notifications
        </h1>

        <p>
            Stay updated about your reservations
            and LuxeStay account.
        </p>

    </div>

    <?php if ($unreadCount > 0): ?>

        <form method="POST">

            <button
                type="submit"
                name="mark_all_read"
                class="mark-all-button"
            >
                Mark All as Read
            </button>

        </form>

    <?php endif; ?>

</div>

<div class="notification-list">

    <?php if (!$notifications): ?>

        <div class="empty-notifications">

            <h2>
                No notifications yet
            </h2>

            <p>
                You will see reservation updates
                and other important messages here.
            </p>

        </div>

    <?php else: ?>

        <?php foreach ($notifications as $notification): ?>

            <?php
            $isUnread =
                (int) ($notification['is_read'] ?? 0) === 0;
            ?>

            <article
                class="notification-card
                <?= $isUnread ? 'unread' : '' ?>"
            >

                <div class="notification-top">

                    <div>

                        <h2 class="notification-title">

                            <?= htmlspecialchars(
                                $notification['title']
                            ) ?>

                        </h2>

                        <p class="notification-date">

                            <?= htmlspecialchars(
                                $notification['created_at']
                            ) ?>

                        </p>

                    </div>

                    <?php if (!$isUnread): ?>

                        <span class="read-label">
                            Read
                        </span>

                    <?php endif; ?>

                </div>

                <p class="notification-message">

                    <?= nl2br(
                        htmlspecialchars(
                            $notification['message']
                        )
                    ) ?>

                </p>

                <?php if ($isUnread): ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?= (int) $notification['id'] ?>"
                        >

                        <button
                            type="submit"
                            name="mark_read"
                            class="read-button"
                        >
                            Mark as Read
                        </button>

                    </form>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<a
    href="/index.php"
    class="back-link"
>
    ← Back to LuxeStay Home
</a>
```

</main>

</body>

</html>
