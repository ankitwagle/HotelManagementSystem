<?php

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

/*
 * TEMPORARY FRONTEND DATA.
 *
 * The partner will replace this with database data later.
 */
$notifications = [

    [
        'id' => 1,
        'customer' => 'John Smith',
        'title' => 'Reservation Request',
        'message' => 'A new reservation request has been submitted.',
        'type' => 'reservation',
        'status' => 'Unread',
        'created_at' => 'Today, 10:30 AM'
    ],

    [
        'id' => 2,
        'customer' => 'Sarah Johnson',
        'title' => 'Payment Completed',
        'message' => 'Payment has been completed for reservation #1002.',
        'type' => 'payment',
        'status' => 'Read',
        'created_at' => 'Today, 9:15 AM'
    ],

    [
        'id' => 3,
        'customer' => 'Michael Brown',
        'title' => 'Cancellation Request',
        'message' => 'A customer has cancelled reservation #1003.',
        'type' => 'reservation',
        'status' => 'Unread',
        'created_at' => 'Yesterday, 6:40 PM'
    ]

];

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
        Notifications | Admin | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/admin.css"
    >

    <style>

        .admin-notifications-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        .notification-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .notification-summary {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .summary-box {
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .summary-box strong {
            display: block;
            font-size: 22px;
            margin-bottom: 3px;
        }

        .admin-notification-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .admin-notification {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            padding: 22px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .admin-notification.unread {
            border-left: 5px solid #14213d;
            background: #f8fafc;
        }

        .admin-notification-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef2ff;
            font-size: 20px;
        }

        .admin-notification-content {
            flex: 1;
        }

        .admin-notification-content h3 {
            margin: 0 0 7px;
        }

        .admin-notification-content p {
            margin: 0 0 8px;
            line-height: 1.6;
        }

        .customer-name {
            font-weight: 700;
        }

        .notification-date {
            color: #64748b;
            font-size: 13px;
        }

        .notification-label {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .notification-label.unread {
            background: #fef3c7;
            color: #92400e;
        }

        .notification-label.read {
            background: #dcfce7;
            color: #166534;
        }

        .admin-read-button {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 8px 12px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .admin-read-button:hover {
            background: #f1f5f9;
        }

        .empty-admin-notifications {
            padding: 60px 20px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

    </style>

</head>

<body>

<header>

    <div class="logo">
        🛏 LuxeStay Admin
    </div>

    <nav>

        <a href="/views/dashboard/admin.php">
            Dashboard
        </a>

        <a href="/views/admin/reservations.php">
            Reservations
        </a>

        <a href="/views/admin/bookings.php">
            Bookings
        </a>

        <a href="/views/admin/customers.php">
            Customers
        </a>

        <a href="/views/admin/rooms.php">
            Rooms
        </a>

        <a href="/views/admin/payments.php">
            Payments
        </a>

        <a href="/views/admin/notifications.php">
            Notifications
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main>

    <section class="welcome">

        <p class="eyebrow">
            ADMIN PANEL
        </p>

        <h1>
            Notifications
        </h1>

        <p>
            Monitor reservation, payment and customer activity.
        </p>

    </section>


    <section class="admin-notifications-wrapper">

        <div class="notification-toolbar">

            <div class="notification-summary">

                <div class="summary-box">

                    <strong>
                        <?= count($notifications) ?>
                    </strong>

                    Total Notifications

                </div>

                <div class="summary-box">

                    <strong>
                        <?= count(
                            array_filter(
                                $notifications,
                                function ($notification) {
                                    return $notification['status'] === 'Unread';
                                }
                            )
                        ) ?>
                    </strong>

                    Unread

                </div>

            </div>

        </div>


        <?php if (empty($notifications)): ?>

            <div class="empty-admin-notifications">

                <h2>
                    No Notifications
                </h2>

                <p>
                    There are currently no system notifications.
                </p>

            </div>

        <?php else: ?>

            <div class="admin-notification-list">

                <?php foreach ($notifications as $notification): ?>

                    <?php
                    $isUnread =
                        $notification['status'] === 'Unread';
                    ?>

                    <article
                        class="admin-notification <?= $isUnread ? 'unread' : '' ?>"
                    >

                        <div class="admin-notification-icon">

                            <?php if (
                                $notification['type'] === 'reservation'
                            ): ?>

                                📅

                            <?php elseif (
                                $notification['type'] === 'payment'
                            ): ?>

                                💳

                            <?php else: ?>

                                🔔

                            <?php endif; ?>

                        </div>


                        <div class="admin-notification-content">

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

                            <p>

                                Customer:

                                <span class="customer-name">
                                    <?= htmlspecialchars(
                                        $notification['customer']
                                    ) ?>
                                </span>

                            </p>

                            <span class="notification-date">

                                <?= htmlspecialchars(
                                    $notification['created_at']
                                ) ?>

                            </span>

                            <br>

                            <span
                                class="notification-label <?= $isUnread ? 'unread' : 'read' ?>"
                            >
                                <?= htmlspecialchars(
                                    $notification['status']
                                ) ?>
                            </span>

                        </div>


                        <?php if ($isUnread): ?>

                            <button
                                type="button"
                                class="admin-read-button"
                            >
                                Mark Read
                            </button>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>


<footer>

    <div>

        <strong>
            LuxeStay
        </strong>

        <p>
            Hotel Management Administration
        </p>

    </div>

</footer>


<script>

document.addEventListener('DOMContentLoaded', function () {

    document
        .querySelectorAll('.admin-read-button')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const notification =
                    button.closest(
                        '.admin-notification'
                    );

                if (!notification) {
                    return;
                }

                notification.classList
                    .remove('unread');

                const label =
                    notification.querySelector(
                        '.notification-label'
                    );

                if (label) {

                    label.classList
                        .remove('unread');

                    label.classList
                        .add('read');

                    label.textContent =
                        'Read';

                }

                button.remove();

            });

        });

});

</script>

</body>

</html>