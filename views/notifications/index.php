<?php
/*
 * Customer Notifications Page
 *
 * Frontend only.
 * The notification data below is temporary demo data.
 *
 * The backend/database will be connected by the partner later.
 */

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

/*
 * TEMPORARY FRONTEND DATA
 *
 * Replace this section later with data from
 * NotificationController.
 */
$notifications = [

    [
        'id' => 1,
        'title' => 'Reservation Confirmed',
        'message' => 'Your reservation #1001 has been confirmed by LuxeStay.',
        'type' => 'reservation',
        'is_read' => false,
        'created_at' => 'Today, 10:30 AM'
    ],

    [
        'id' => 2,
        'title' => 'Payment Successful',
        'message' => 'Your payment for reservation #1001 was completed successfully.',
        'type' => 'payment',
        'is_read' => false,
        'created_at' => 'Yesterday, 4:20 PM'
    ],

    [
        'id' => 3,
        'title' => 'Welcome to LuxeStay',
        'message' => 'Thank you for choosing LuxeStay. We hope you enjoy your stay.',
        'type' => 'general',
        'is_read' => true,
        'created_at' => '2 days ago'
    ]

];

$unreadCount = 0;

foreach ($notifications as $notification) {

    if (!$notification['is_read']) {
        $unreadCount++;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Notifications | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .notification-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .notification-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 10px;
            border-radius: 999px;
            background: #14213d;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }

        .mark-all-button {
            border: 0;
            background: #14213d;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .mark-all-button:hover {
            background: #0f172a;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .notification-item {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            padding: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow:
                0 5px 18px rgba(15, 23, 42, 0.05);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .notification-item.unread {
            border-left: 5px solid #14213d;
            background: #f8fafc;
        }

        .notification-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef2ff;
            font-size: 21px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content h3 {
            margin: 0 0 7px;
        }

        .notification-content p {
            margin: 0 0 8px;
            line-height: 1.6;
        }

        .notification-time {
            font-size: 13px;
            color: #64748b;
        }

        .unread-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #14213d;
            margin-top: 8px;
        }

        .notification-actions {
            margin-top: 12px;
        }

        .read-button {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #14213d;
            padding: 7px 11px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .read-button:hover {
            background: #f1f5f9;
        }

        .empty-notifications {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        .empty-notifications .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        @media (max-width: 650px) {

            .notification-item {
                padding: 17px;
                gap: 12px;
            }

            .notification-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<header>

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

        <a href="/views/notifications/index.php">
            Notifications
        </a>

        <a href="/views/payments/index.php">
            Payments
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>

<main>

    <section class="welcome">

        <p class="eyebrow">
            LUXESTAY
        </p>

        <h1>
            Notifications
        </h1>

        <p>
            Stay updated with your reservations,
            payments and LuxeStay announcements.
        </p>

    </section>


    <section class="welcome">

        <div class="notification-wrapper">

            <div class="notification-header">

                <div>

                    <h2>
                        Your Notifications

                        <span
                            class="notification-count"
                            id="unreadCount"
                        >
                            <?= $unreadCount ?>
                        </span>

                    </h2>

                    <p>
                        <?= $unreadCount ?>
                        unread notification<?= $unreadCount === 1 ? '' : 's' ?>
                    </p>

                </div>

                <?php if ($unreadCount > 0): ?>

                    <button
                        type="button"
                        class="mark-all-button"
                        id="markAllButton"
                    >
                        Mark All as Read
                    </button>

                <?php endif; ?>

            </div>


            <?php if (empty($notifications)): ?>

                <div class="empty-notifications">

                    <div class="empty-icon">
                        🔔
                    </div>

                    <h2>
                        No Notifications
                    </h2>

                    <p>
                        You don't have any notifications yet.
                    </p>

                </div>

            <?php else: ?>

                <div
                    class="notifications-list"
                    id="notificationsList"
                >

                    <?php foreach ($notifications as $notification): ?>

                        <article
                            class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>"
                            data-id="<?= (int) $notification['id'] ?>"
                        >

                            <div class="notification-icon">

                                <?php if ($notification['type'] === 'reservation'): ?>

                                    📅

                                <?php elseif ($notification['type'] === 'payment'): ?>

                                    💳

                                <?php else: ?>

                                    🔔

                                <?php endif; ?>

                            </div>


                            <div class="notification-content">

                                <h3>
                                    <?= htmlspecialchars(
                                        $notification['title']
                                    ) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        $notification['message']
                                    ) ?>
                                </p>

                                <span class="notification-time">
                                    <?= htmlspecialchars(
                                        $notification['created_at']
                                    ) ?>
                                </span>


                                <?php if (!$notification['is_read']): ?>

                                    <div class="notification-actions">

                                        <button
                                            type="button"
                                            class="read-button"
                                        >
                                            Mark as Read
                                        </button>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <?php if (!$notification['is_read']): ?>

                                <span
                                    class="unread-dot"
                                    title="Unread notification"
                                ></span>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<footer>

    <div>

        <strong>
            LuxeStay
        </strong>

        <p>
            Excellence in hospitality and refined comfort.
        </p>

    </div>

</footer>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const markAllButton =
        document.getElementById('markAllButton');

    const unreadCount =
        document.getElementById('unreadCount');

    const notifications =
        document.querySelectorAll('.notification-item');

    /*
     * Frontend-only "Mark as Read".
     *
     * Later your partner can replace this
     * with a request to NotificationController.
     */
    document.querySelectorAll('.read-button')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const notification =
                    button.closest('.notification-item');

                if (!notification) {
                    return;
                }

                notification.classList.remove('unread');

                const dot =
                    notification.querySelector('.unread-dot');

                if (dot) {
                    dot.remove();
                }

                button.remove();

                updateUnreadCount();

            });

        });


    /*
     * Frontend-only "Mark All as Read".
     */
    if (markAllButton) {

        markAllButton.addEventListener(
            'click',
            function () {

                notifications.forEach(
                    function (notification) {

                        notification.classList
                            .remove('unread');

                        const dot =
                            notification
                                .querySelector(
                                    '.unread-dot'
                                );

                        if (dot) {
                            dot.remove();
                        }

                        const button =
                            notification
                                .querySelector(
                                    '.read-button'
                                );

                        if (button) {
                            button.remove();
                        }

                    }
                );

                updateUnreadCount();

                markAllButton.remove();

            }
        );

    }


    function updateUnreadCount() {

        const unread =
            document.querySelectorAll(
                '.notification-item.unread'
            ).length;

        if (unreadCount) {
            unreadCount.textContent = unread;
        }

    }

});

</script>

</body>

</html>