<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Customer Access
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(403);
    exit('Invalid user session.');
}

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| Customer Information
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        address
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header('Location: /login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Reservation Statistics
|--------------------------------------------------------------------------
*/

$totalReservations = 0;
$activeReservations = 0;
$completedReservations = 0;

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    $totalReservations = (int) $stmt->fetchColumn();


    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE user_id = ?
        AND LOWER(status) IN ('pending', 'approved', 'confirmed')
    ");

    $stmt->execute([$userId]);

    $activeReservations = (int) $stmt->fetchColumn();


    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE user_id = ?
        AND LOWER(status) IN ('completed', 'checked_out')
    ");

    $stmt->execute([$userId]);

    $completedReservations = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $totalReservations = 0;
    $activeReservations = 0;
    $completedReservations = 0;
}

/*
|--------------------------------------------------------------------------
| Payment Statistics
|--------------------------------------------------------------------------
*/

$totalPaid = 0;
$paymentCount = 0;

try {

    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(amount), 0),
            COUNT(*)
        FROM payments
        WHERE user_id = ?
        AND LOWER(status) = 'paid'
    ");

    $stmt->execute([$userId]);

    $paymentData = $stmt->fetch(PDO::FETCH_NUM);

    $totalPaid = (float) ($paymentData[0] ?? 0);
    $paymentCount = (int) ($paymentData[1] ?? 0);

} catch (PDOException $e) {

    $totalPaid = 0;
    $paymentCount = 0;
}

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

$unreadNotifications = 0;

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    $stmt->execute([$userId]);

    $unreadNotifications = (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $unreadNotifications = 0;
}

/*
|--------------------------------------------------------------------------
| Recent Reservations
|--------------------------------------------------------------------------
*/

$recentReservations = [];

try {

    $stmt = $db->prepare("
        SELECT
            r.id,
            r.check_in,
            r.check_out,
            r.status,
            r.created_at,
            rm.room_number
        FROM reservations r
        LEFT JOIN rooms rm
            ON rm.id = r.room_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");

    $stmt->execute([$userId]);

    $recentReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $recentReservations = [];
}

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
    My Dashboard | LuxeStay
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .customer-dashboard {
        max-width: 1200px;
        margin: 0 auto;
        padding: 45px 20px 70px;
    }

    .dashboard-header {
        margin-bottom: 35px;
    }

    .dashboard-header h1 {
        color: #102a4c;
        margin-bottom: 8px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 35px;
    }

    .dashboard-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px;
        box-shadow:
            0 5px 20px rgba(15, 23, 42, 0.06);
    }

    .dashboard-card h2 {
        color: #102a4c;
        font-size: 28px;
        margin: 8px 0;
    }

    .dashboard-card p {
        color: #64748b;
        margin-bottom: 0;
    }

    .dashboard-card a {
        display: inline-block;
        margin-top: 14px;
        color: #8b6f3d;
        font-weight: 700;
        text-decoration: none;
    }

    .dashboard-card a:hover {
        text-decoration: underline;
    }

    .notification-card {
        border-top: 4px solid #b28a2e;
    }

    .payment-card {
        border-top: 4px solid #198754;
    }

    .reservation-section {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 25px;
        box-shadow:
            0 5px 20px rgba(15, 23, 42, 0.06);
    }

    .reservation-section h2 {
        color: #102a4c;
    }

    .table-wrapper {
        overflow-x: auto;
        margin-top: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 13px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    th {
        background: #102a4c;
        color: white;
    }

    .status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .status.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status.approved,
    .status.confirmed {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status.completed,
    .status.checked_out {
        background: #e2e3e5;
        color: #41464b;
    }

    .status.rejected,
    .status.cancelled {
        background: #f8d7da;
        color: #842029;
    }

    .empty {
        text-align: center;
        padding: 30px;
        color: #64748b;
    }

    .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 25px;
    }

    .action-button {
        display: inline-block;
        padding: 11px 17px;
        border-radius: 7px;
        text-decoration: none;
        font-weight: 700;
        background: #102a4c;
        color: white;
    }

    .action-button.gold {
        background: #8b6f3d;
    }

    .action-button.green {
        background: #198754;
    }

    @media (max-width: 900px) {

        .dashboard-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

    }

    @media (max-width: 600px) {

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .customer-dashboard {
            padding-left: 15px;
            padding-right: 15px;
        }

    }

</style>
```

</head>

<body>

<header>

```
<div class="logo">
    <a
        href="/index.php"
        style="text-decoration:none;color:inherit;"
    >
        🛏 LuxeStay
    </a>
</div>

<nav>

    <a href="/index.php">
        Home
    </a>

    <a href="/views/customers/index.php">
        Dashboard
    </a>

    <a href="/views/customers/profile.php">
        Profile
    </a>

    <a href="/views/customer/notifications.php">
        🔔 Notifications

        <?php if ($unreadNotifications > 0): ?>

            (<?= $unreadNotifications ?>)

        <?php endif; ?>

    </a>

    <a href="/logout.php">
        Logout
    </a>

</nav>
```

</header>

<main class="customer-dashboard">

```
<section class="dashboard-header">

    <p class="eyebrow">
        CUSTOMER DASHBOARD
    </p>

    <h1>
        Welcome,
        <?= htmlspecialchars($customer['name']) ?>.
    </h1>

    <p>
        Manage your LuxeStay reservations,
        payments, profile, and notifications.
    </p>

</section>


<section class="dashboard-grid">

    <article class="dashboard-card">

        <p class="eyebrow">
            RESERVATIONS
        </p>

        <h2>
            <?= number_format($totalReservations) ?>
        </h2>

        <p>
            Total reservations.
        </p>

        <a href="/reservation.php">
            Make a Reservation →
        </a>

    </article>


    <article class="dashboard-card">

        <p class="eyebrow">
            ACTIVE STAYS
        </p>

        <h2>
            <?= number_format($activeReservations) ?>
        </h2>

        <p>
            Pending or confirmed reservations.
        </p>

    </article>


    <article class="dashboard-card payment-card">

        <p class="eyebrow">
            PAID
        </p>

        <h2>
            $<?= number_format($totalPaid, 2) ?>
        </h2>

        <p>
            Total successful payments.
        </p>

    </article>


    <article class="dashboard-card notification-card">

        <p class="eyebrow">
            NOTIFICATIONS
        </p>

        <h2>
            <?= number_format($unreadNotifications) ?>
        </h2>

        <p>
            Unread notifications.
        </p>

        <a href="/views/customer/notifications.php">
            View Notifications →
        </a>

    </article>

</section>


<section class="reservation-section">

    <p class="eyebrow">
        RECENT ACTIVITY
    </p>

    <h2>
        My Recent Reservations
    </h2>

    <div class="table-wrapper">

        <?php if (!$recentReservations): ?>

            <div class="empty">

                <p>
                    You do not have any reservations yet.
                </p>

            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Reservation
                        </th>

                        <th>
                            Room
                        </th>

                        <th>
                            Check-in
                        </th>

                        <th>
                            Check-out
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($recentReservations as $reservation): ?>

                    <?php
                    $status = strtolower(
                        $reservation['status'] ?? ''
                    );
                    ?>

                    <tr>

                        <td>
                            #<?= (int) $reservation['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['room_number'] ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['check_in'] ?? '-'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['check_out'] ?? '-'
                            ) ?>
                        </td>

                        <td>

                            <span
                                class="status <?= htmlspecialchars($status) ?>"
                            >
                                <?= htmlspecialchars(
                                    $reservation['status']
                                ) ?>
                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>


    <div class="quick-actions">

        <a
            class="action-button"
            href="/reservation.php"
        >
            Make Reservation
        </a>

        <a
            class="action-button gold"
            href="/views/customers/profile.php"
        >
            My Profile
        </a>

        <a
            class="action-button green"
            href="/views/customer/notifications.php"
        >
            Notifications
        </a>

    </div>

</section>
```

</main>

</body>

</html>
