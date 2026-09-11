<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Admin Access
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin access required.');
}

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| Basic Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'guest'
    ")
    ->fetchColumn();

$totalRooms = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM rooms
    ")
    ->fetchColumn();

$totalReservations = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
    ")
    ->fetchColumn();

$pendingReservations = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE LOWER(status) = 'pending'
    ")
    ->fetchColumn();

$todayBookings = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE DATE(created_at) = CURDATE()
    ")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| Payment Statistics
|--------------------------------------------------------------------------
*/

$todayRevenue = 0;
$totalPaidRevenue = 0;
$totalPayments = 0;
$paidPayments = 0;
$pendingPayments = 0;
$refundRequests = 0;

try {

    $todayRevenue = (float) $db
        ->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE LOWER(status) = 'paid'
            AND DATE(paid_at) = CURDATE()
        ")
        ->fetchColumn();

    $totalPaidRevenue = (float) $db
        ->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE LOWER(status) = 'paid'
        ")
        ->fetchColumn();

    $totalPayments = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
        ")
        ->fetchColumn();

    $paidPayments = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
            WHERE LOWER(status) = 'paid'
        ")
        ->fetchColumn();

    $pendingPayments = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
            WHERE LOWER(status) IN ('pending', 'unpaid')
        ")
        ->fetchColumn();

    $refundRequests = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
            WHERE LOWER(status) = 'refund_requested'
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $todayRevenue = 0;
    $totalPaidRevenue = 0;
    $totalPayments = 0;
    $paidPayments = 0;
    $pendingPayments = 0;
    $refundRequests = 0;
}

/*
|--------------------------------------------------------------------------
| Check-in / Check-out Statistics
|--------------------------------------------------------------------------
*/

$checkInsToday = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE check_in = CURDATE()
        AND LOWER(status) IN ('approved', 'confirmed')
    ")
    ->fetchColumn();

$checkOutsToday = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE check_out = CURDATE()
        AND LOWER(status) IN ('approved', 'confirmed')
    ")
    ->fetchColumn();

$occupiedRooms = (int) $db
    ->query("
        SELECT COUNT(DISTINCT room_id)
        FROM reservations
        WHERE check_in <= CURDATE()
        AND check_out > CURDATE()
        AND LOWER(status) IN ('approved', 'confirmed')
    ")
    ->fetchColumn();

$occupancy = $totalRooms > 0
    ? round(($occupiedRooms / $totalRooms) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Room Availability Overview
|--------------------------------------------------------------------------
|
| Shows every room and its current / next reservation.
|
| Priority:
|
| 1. Occupied Now
| 2. Upcoming Reservation
| 3. Available
|
*/

$roomAvailability = [];

try {

    $roomAvailabilityStmt = $db->query("
        SELECT
            rm.id,
            rm.room_number,
            rm.room_type,
            rm.price,
            rm.capacity,
            rm.floor,
            rm.status AS room_status,

            current_res.id AS current_reservation_id,
            current_res.check_in AS current_check_in,
            current_res.check_out AS current_check_out,
            current_user.name AS current_guest_name,

            next_res.id AS next_reservation_id,
            next_res.check_in AS next_check_in,
            next_res.check_out AS next_check_out,
            next_user.name AS next_guest_name

        FROM rooms rm

        LEFT JOIN reservations current_res
            ON current_res.id = (
                SELECT r1.id
                FROM reservations r1
                WHERE r1.room_id = rm.id
                AND r1.status IN ('pending', 'confirmed')
                AND r1.check_in <= CURDATE()
                AND r1.check_out > CURDATE()
                ORDER BY r1.check_out ASC
                LIMIT 1
            )

        LEFT JOIN users current_user
            ON current_user.id = current_res.user_id

        LEFT JOIN reservations next_res
            ON next_res.id = (
                SELECT r2.id
                FROM reservations r2
                WHERE r2.room_id = rm.id
                AND r2.status IN ('pending', 'confirmed')
                AND r2.check_in > CURDATE()
                ORDER BY r2.check_in ASC
                LIMIT 1
            )

        LEFT JOIN users next_user
            ON next_user.id = next_res.user_id

        ORDER BY rm.room_number ASC
    ");

    $roomAvailability = $roomAvailabilityStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $roomAvailability = [];
}

/*
|--------------------------------------------------------------------------
| Feedback Statistics
|--------------------------------------------------------------------------
*/

$feedbackCount = 0;

try {

    $feedbackCount = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM feedback
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $feedbackCount = 0;
}

/*
|--------------------------------------------------------------------------
| Daily Revenue Chart - Last 7 Days
|--------------------------------------------------------------------------
*/

$chartLabels = [];
$chartRevenue = [];

try {

    $chartStmt = $db->query("
        SELECT
            DATE(paid_at) AS payment_date,
            COALESCE(SUM(amount), 0) AS revenue
        FROM payments
        WHERE LOWER(status) = 'paid'
        AND paid_at IS NOT NULL
        AND DATE(paid_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(paid_at)
        ORDER BY payment_date ASC
    ");

    $chartRows =
        $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Create all 7 dates.
     * This also shows dates with $0 revenue.
     */
    $revenueByDate = [];

    foreach ($chartRows as $row) {

        $revenueByDate[
            $row['payment_date']
        ] = (float) $row['revenue'];
    }

    for ($i = 6; $i >= 0; $i--) {

        $date = date(
            'Y-m-d',
            strtotime("-{$i} days")
        );

        $chartLabels[] = date(
            'M j',
            strtotime($date)
        );

        $chartRevenue[] =
            $revenueByDate[$date] ?? 0;
    }

} catch (PDOException $e) {

    $chartLabels = [];
    $chartRevenue = [];
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
    Admin Dashboard |
    <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>

<style>

    .admin-dashboard {
        max-width: 1250px;
        margin: 0 auto;
        padding: 45px 25px 80px;
    }

    .admin-header {
        margin-bottom: 40px;
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Analytics
    |--------------------------------------------------------------------------
    */

    .payment-analytics {
        margin-bottom: 35px;
    }

    .payment-analytics-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
    }

    .payment-analytics-header h2 {
        margin-bottom: 8px;
    }

    .payment-analytics-header p {
        margin-bottom: 0;
        color: #64748b;
    }

    .payment-chart-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 28px;
        box-shadow:
            0 8px 25px rgba(15, 23, 42, 0.06);
    }

    .chart-wrapper {
        position: relative;
        height: 390px;
        margin-top: 25px;
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Overview Button
    |--------------------------------------------------------------------------
    */

    .overview-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border: 0;
        border-radius: 9px;
        background: #14213d;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        transition:
            background 0.2s ease,
            transform 0.2s ease;
    }

    .overview-toggle:hover {
        background: #0f172a;
        transform: translateY(-1px);
    }

    .overview-toggle .arrow {
        transition: transform 0.25s ease;
    }

    .overview-toggle.active .arrow {
        transform: rotate(180deg);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Overview
    |--------------------------------------------------------------------------
    */

    .payment-overview {
        display: none;
        margin-top: 20px;
        animation: fadeIn 0.25s ease;
    }

    .payment-overview.show {
        display: block;
    }

    .payment-summary {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .summary-box {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 13px;
        padding: 20px;
        box-shadow:
            0 5px 18px rgba(15, 23, 42, 0.04);
    }

    .summary-box strong {
        display: block;
        margin-top: 8px;
        font-size: 24px;
        color: #14213d;
    }

    .summary-box.pending {
        border-top: 4px solid #d97706;
    }

    .summary-box.paid {
        border-top: 4px solid #198754;
    }

    .summary-box.revenue {
        border-top: 4px solid #8b6f3d;
    }

    .summary-box.total {
        border-top: 4px solid #14213d;
    }

    /*
    |--------------------------------------------------------------------------
    | Hotel Performance
    |--------------------------------------------------------------------------
    */

    .stats-grid {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 35px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 22px;
        box-shadow:
            0 6px 20px rgba(15, 23, 42, 0.05);
    }

    .stat-card h2 {
        margin: 8px 0;
        font-size: 28px;
        color: #14213d;
    }

    .stat-card p {
        color: #64748b;
        margin-bottom: 0;
    }

    .stat-link {
        display: inline-block;
        margin-top: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .payment-highlight {
        border-top: 4px solid #198754;
    }

    .pending-highlight {
        border-top: 4px solid #d97706;
    }

    .revenue-highlight {
        border-top: 4px solid #8b6f3d;
    }

    /*
    |--------------------------------------------------------------------------
    | Room Availability
    |--------------------------------------------------------------------------
    */

    .room-overview {
        margin-top: 45px;
        margin-bottom: 45px;
    }

    .room-overview-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        margin-bottom: 20px;
    }

    .room-overview-header h2 {
        margin: 0 0 8px;
    }

    .room-overview-header p {
        margin: 0;
        color: #64748b;
    }

    .room-table-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        box-shadow:
            0 8px 25px rgba(15, 23, 42, 0.06);
    }

    .room-table-wrapper {
        overflow-x: auto;
        width: 100%;
    }

    .room-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .room-table th {
        background: #f8fafc;
        padding: 14px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        border-bottom: 1px solid #e5e7eb;
    }

    .room-table td {
        padding: 16px 14px;
        border-bottom: 1px solid #edf0f3;
        font-size: 13px;
        vertical-align: middle;
    }

    .room-table tr:last-child td {
        border-bottom: none;
    }

    .room-table tbody tr:hover {
        background: #fafafa;
    }

    .room-number {
        font-weight: 700;
        color: #14213d;
    }

    .guest-name {
        font-weight: 600;
    }

    .availability-status {
        display: inline-block;
        padding: 6px 11px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .availability-occupied {
        background: #f8d7da;
        color: #842029;
    }

    .availability-reserved {
        background: #fff3cd;
        color: #856404;
    }

    .availability-available {
        background: #d1e7dd;
        color: #0f5132;
    }

    .availability-maintenance {
        background: #e2e3e5;
        color: #41464b;
    }

    .booking-details {
        color: #64748b;
        font-size: 12px;
        line-height: 1.6;
    }

    .manage-rooms-btn {
        display: inline-block;
        padding: 11px 18px;
        background: #14213d;
        color: #ffffff;
        text-decoration: none;
        border-radius: 9px;
        font-weight: 700;
    }

    .manage-rooms-btn:hover {
        background: #0f172a;
    }

    .empty-room-data {
        text-align: center;
        padding: 40px;
        color: #64748b;
    }

    /*
    |--------------------------------------------------------------------------
    | Management
    |--------------------------------------------------------------------------
    */

    .management-section {
        margin-top: 40px;
    }

    /*
    |--------------------------------------------------------------------------
    | Animation
    |--------------------------------------------------------------------------
    */

    @keyframes fadeIn {

        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1000px) {

        .stats-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .payment-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

    }

    @media (max-width: 650px) {

        .admin-dashboard {
            padding-left: 15px;
            padding-right: 15px;
        }

        .stats-grid,
        .payment-summary {
            grid-template-columns: 1fr;
        }

        .payment-analytics-header,
        .room-overview-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .chart-wrapper {
            height: 300px;
        }

    }

</style>

</head>

<body>

<header>

    <div class="logo">

        <a
            href="/index.php"
            style="
                color: inherit;
                text-decoration: none;
            "
        >
            🛏 LuxeStay
        </a>

    </div>

    <nav>

        <a href="/index.php">
            Home
        </a>

        <a href="/views/admin/index.php">
            Dashboard
        </a>

        <a href="/views/admin/reservations.php">
            Reservations
        </a>

        <a href="/views/admin/customers.php">
            Guests
        </a>

        <a href="/views/admin/rooms.php">
            Rooms
        </a>

        <a href="/views/admin/payments.php">
            Payments
        </a>

        <a href="/views/admin/reports.php">
            Reports
        </a>

        <a href="/views/notifications/index.php">
            Notifications
        </a>

        <a href="/views/customers/profile.php">
            Profile
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>

<main class="admin-dashboard">


<!-- ADMIN HEADER -->

<section class="admin-header">

    <p class="eyebrow">
        LUXESTAY ADMINISTRATION
    </p>

    <h1>
        Admin Dashboard
    </h1>

    <p>
        Welcome,
        <?= htmlspecialchars(
            $_SESSION['user']['name']
            ?? 'Administrator'
        ) ?>.
    </p>

    <p>
        <?= date('l, F j, Y') ?>
    </p>

</section>


<!-- PAYMENT ANALYTICS -->

<section class="payment-analytics">

    <div class="payment-analytics-header">

        <div>

            <p class="eyebrow">
                PAYMENT ANALYTICS
            </p>

            <h2>
                Customer Payment Revenue
            </h2>

            <p>
                Monthly revenue from successfully completed payments.
            </p>

        </div>

        <button
            type="button"
            id="overviewToggle"
            class="overview-toggle"
        >
            Payment Overview
            <span class="arrow">
                ▼
            </span>
        </button>

    </div>


<article class="payment-chart-card">

    <div class="revenue-card-header">

        <div>

            <p class="revenue-title">
                Performance Overview
            </p>

            <div class="revenue-main">

                <h2>
                    $<?= number_format(
                        array_sum($chartRevenue),
                        2
                    ) ?>
                </h2>

                <span class="revenue-label">
                    Revenue - Last 7 Days
                </span>

            </div>

        </div>

        <div class="date-badge">
            Last 7 Days
        </div>

    </div>


    <div class="chart-wrapper">

        <canvas
            id="paymentRevenueChart"
        ></canvas>

    </div>


    <div class="revenue-footer">

        <span>
            Daily Revenue
        </span>

        <strong>
            <?= date(
                'M j',
                strtotime('-6 days')
            ) ?>
            -
            <?= date('M j, Y') ?>
        </strong>

    </div>

</article>


    <div
        id="paymentOverview"
        class="payment-overview"
    >

        <div class="payment-summary">

            <article class="summary-box revenue">

                <span class="eyebrow">
                    TODAY'S PAID REVENUE
                </span>

                <strong>
                    $<?= number_format(
                        $todayRevenue,
                        2
                    ) ?>
                </strong>

            </article>


            <article class="summary-box total">

                <span class="eyebrow">
                    TOTAL PAID REVENUE
                </span>

                <strong>
                    $<?= number_format(
                        $totalPaidRevenue,
                        2
                    ) ?>
                </strong>

            </article>


            <article class="summary-box pending">

                <span class="eyebrow">
                    PENDING PAYMENTS
                </span>

                <strong>
                    <?= number_format(
                        $pendingPayments
                    ) ?>
                </strong>

            </article>


            <article class="summary-box paid">

                <span class="eyebrow">
                    COMPLETED PAYMENTS
                </span>

                <strong>
                    <?= number_format(
                        $paidPayments
                    ) ?>
                </strong>

            </article>

        </div>

    </div>

</section>


<!-- HOTEL PERFORMANCE -->

<section class="welcome">

    <p class="eyebrow">
        TODAY'S OVERVIEW
    </p>

    <h2>
        Hotel Performance
    </h2>

    <div class="stats-grid">


        <article class="stat-card">

            <p class="eyebrow">
                TODAY'S BOOKINGS
            </p>

            <h2>
                <?= number_format(
                    $todayBookings
                ) ?>
            </h2>

            <p>
                New reservations today.
            </p>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                TOTAL RESERVATIONS
            </p>

            <h2>
                <?= number_format(
                    $totalReservations
                ) ?>
            </h2>

            <p>
                All reservations.
            </p>

            <a
                class="stat-link"
                href="/views/admin/reservations.php"
            >
                Manage Reservations →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                PENDING RESERVATIONS
            </p>

            <h2>
                <?= number_format(
                    $pendingReservations
                ) ?>
            </h2>

            <p>
                Reservations requiring attention.
            </p>

            <a
                class="stat-link"
                href="/views/admin/reservations.php"
            >
                Review Pending →
            </a>

        </article>


        <article class="stat-card revenue-highlight">

            <p class="eyebrow">
                TODAY'S REVENUE
            </p>

            <h2>
                $<?= number_format(
                    $todayRevenue,
                    2
                ) ?>
            </h2>

            <p>
                Actual successful payments received today.
            </p>

            <a
                class="stat-link"
                href="/views/admin/payments.php"
            >
                View Payments →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                CUSTOMERS
            </p>

            <h2>
                <?= number_format(
                    $totalUsers
                ) ?>
            </h2>

            <p>
                Registered customers.
            </p>

            <a
                class="stat-link"
                href="/views/admin/customers.php"
            >
                Manage Customers →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                AVAILABLE ROOMS
            </p>

            <h2>
                <?= number_format(
                    max(
                        0,
                        $totalRooms - $occupiedRooms
                    )
                ) ?>
            </h2>

            <p>
                Currently available.
            </p>

            <a
                class="stat-link"
                href="/views/admin/rooms.php"
            >
                Manage Rooms →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                CHECK-INS TODAY
            </p>

            <h2>
                <?= number_format(
                    $checkInsToday
                ) ?>
            </h2>

            <p>
                Guests arriving today.
            </p>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                CHECK-OUTS TODAY
            </p>

            <h2>
                <?= number_format(
                    $checkOutsToday
                ) ?>
            </h2>

            <p>
                Guests departing today.
            </p>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                OCCUPIED ROOMS
            </p>

            <h2>
                <?= number_format(
                    $occupiedRooms
                ) ?>
            </h2>

            <p>
                Rooms occupied today.
            </p>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                OCCUPANCY
            </p>

            <h2>
                <?= $occupancy ?>%
            </h2>

            <p>
                Current room occupancy.
            </p>

        </article>


        <article class="stat-card pending-highlight">

            <p class="eyebrow">
                PENDING PAYMENTS
            </p>

            <h2>
                <?= number_format(
                    $pendingPayments
                ) ?>
            </h2>

            <p>
                Payments requiring attention.
            </p>

            <a
                class="stat-link"
                href="/views/admin/payments.php"
            >
                Review Payments →
            </a>

        </article>


        <article class="stat-card payment-highlight">

            <p class="eyebrow">
                COMPLETED PAYMENTS
            </p>

            <h2>
                <?= number_format(
                    $paidPayments
                ) ?>
            </h2>

            <p>
                Successfully completed customer payments.
            </p>

            <a
                class="stat-link"
                href="/views/admin/payments.php"
            >
                View Payment Records →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                REFUND REQUESTS
            </p>

            <h2>
                <?= number_format(
                    $refundRequests
                ) ?>
            </h2>

            <p>
                Refund records requiring attention.
            </p>

            <a
                class="stat-link"
                href="/views/admin/payments.php"
            >
                Manage Payments →
            </a>

        </article>


        <article class="stat-card">

            <p class="eyebrow">
                FEEDBACK
            </p>

            <h2>
                <?= number_format(
                    $feedbackCount
                ) ?>
            </h2>

            <p>
                Customer feedback and reviews.
            </p>

        </article>

    </div>

</section>


<!-- ROOM AVAILABILITY OVERVIEW -->

<section class="room-overview">

    <div class="room-overview-header">

        <div>

            <p class="eyebrow">
                LIVE ROOM STATUS
            </p>

            <h2>
                Room Availability Overview
            </h2>

            <p>
                See which rooms are occupied, reserved, or available and view booking dates and guest information.
            </p>

        </div>

        <a
            href="/views/admin/rooms.php"
            class="manage-rooms-btn"
        >
            Manage Rooms →
        </a>

    </div>


    <div class="room-table-card">

        <div class="room-table-wrapper">

            <table class="room-table">

                <thead>

                    <tr>

                        <th>
                            Room
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Capacity
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Guest
                        </th>

                        <th>
                            Check-in
                        </th>

                        <th>
                            Booked Until
                        </th>

                        <th>
                            Booking
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($roomAvailability)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="empty-room-data"
                        >
                            No room data found.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($roomAvailability as $room): ?>

                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | Determine Room Display Status
                        |--------------------------------------------------------------------------
                        */

                        $displayStatus = 'available';
                        $statusLabel = 'Available';

                        $guestName = '—';
                        $checkIn = '—';
                        $checkOut = '—';
                        $bookingId = '—';

                        /*
                        | Room currently occupied
                        */

                        if (
                            !empty(
                                $room['current_reservation_id']
                            )
                        ) {

                            $displayStatus = 'occupied';
                            $statusLabel = 'Occupied';

                            $guestName =
                                $room['current_guest_name']
                                ?? 'Unknown';

                            $checkIn =
                                $room['current_check_in']
                                ?? '—';

                            $checkOut =
                                $room['current_check_out']
                                ?? '—';

                            $bookingId =
                                '#' .
                                (
                                    $room['current_reservation_id']
                                    ?? ''
                                );

                        /*
                        | Future reservation
                        */

                        } elseif (
                            !empty(
                                $room['next_reservation_id']
                            )
                        ) {

                            $displayStatus = 'reserved';
                            $statusLabel = 'Reserved';

                            $guestName =
                                $room['next_guest_name']
                                ?? 'Unknown';

                            $checkIn =
                                $room['next_check_in']
                                ?? '—';

                            $checkOut =
                                $room['next_check_out']
                                ?? '—';

                            $bookingId =
                                '#' .
                                (
                                    $room['next_reservation_id']
                                    ?? ''
                                );

                        /*
                        | Physical room maintenance
                        */

                        } elseif (
                            isset($room['room_status']) &&
                            strtolower($room['room_status'])
                            === 'maintenance'
                        ) {

                            $displayStatus = 'maintenance';
                            $statusLabel = 'Maintenance';

                        }

                        ?>

                        <tr>

                            <td class="room-number">

                                Room
                                <?= htmlspecialchars(
                                    $room['room_number']
                                    ?? $room['id']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $room['room_type']
                                    ?? 'Room'
                                ) ?>

                            </td>


                            <td>

                                <?= (int) (
                                    $room['capacity']
                                    ?? 0
                                ) ?>

                                Guests

                            </td>


                            <td>

                                <span
                                    class="availability-status availability-<?= htmlspecialchars($displayStatus) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $statusLabel
                                    ) ?>

                                </span>

                            </td>


                            <td class="guest-name">

                                <?= htmlspecialchars(
                                    $guestName
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $checkIn
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $checkOut
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $bookingId
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>


<!-- MANAGEMENT -->

<section class="welcome management-section">

    <p class="eyebrow">
        MANAGEMENT
    </p>

    <h2>
        Hotel Management
    </h2>

    <div class="cards">


        <article>

            <h2>
                Reservations
            </h2>

            <p>
                Approve, reject, cancel,
                and manage all customer reservations.
            </p>

            <a href="/views/admin/reservations.php">
                Open Reservations →
            </a>

        </article>


        <article>

            <h2>
                Customers
            </h2>

            <p>
                View registered customers and
                customer account information.
            </p>

            <a href="/views/admin/customers.php">
                Open Customers →
            </a>

        </article>


        <article>

            <h2>
                Rooms
            </h2>

            <p>
                Manage rooms, prices, capacity,
                availability, and room status.
            </p>

            <a href="/views/admin/rooms.php">
                Open Rooms →
            </a>

        </article>


        <article>

            <h2>
                Customer Cash Flow
            </h2>

            <p>
                Review payments, revenue,
                transactions, pending payments,
                and refund requests.
            </p>

            <a href="/views/admin/payments.php">
                Open Cash Flow →
            </a>

        </article>


        <article>

            <h2>
                Notifications
            </h2>

            <p>
                Manage hotel system notifications.
            </p>

            <a href="/views/notifications/index.php">
                Open Notifications →
            </a>

        </article>


        <article>

            <h2>
                Reports
            </h2>

            <p>
                View hotel statistics and
                business performance reports.
            </p>

            <a href="/views/admin/reports.php">
                Open Reports →
            </a>

        </article>

    </div>

</section>


</main>


<footer>

<div>

    <strong>
        LuxeStay
    </strong>

    <p>
        Hotel Management Administration System.
    </p>

</div>

</footer>


<script>

const paymentLabels =
    <?= json_encode(
        $chartLabels,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;

const paymentRevenue =
    <?= json_encode(
        $chartRevenue
    ) ?>;


/*
|--------------------------------------------------------------------------
| Payment Revenue Chart
|--------------------------------------------------------------------------
*/

const chartCanvas =
    document.getElementById(
        'paymentRevenueChart'
    );

if (chartCanvas) {

    new Chart(
        chartCanvas,
        {
            type: 'line',

            data: {

                labels: paymentLabels,

                datasets: [
                    {
                        label: 'Paid Revenue',

                        data: paymentRevenue,

                        borderWidth: 3,

                        tension: 0.35,

                        fill: true,

                        pointRadius: 4,

                        pointHoverRadius: 7
                    }
                ]
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {

                    legend: {
                        display: true
                    },

                    tooltip: {

                        callbacks: {

                            label: function(context) {

                                return ' Revenue: $' +

                                    Number(
                                        context.parsed.y
                                    ).toLocaleString(
                                        'en-US',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    );
                            }
                        }
                    }
                },

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            callback: function(value) {

                                return '$' +

                                    Number(value)
                                    .toLocaleString(
                                        'en-US'
                                    );
                            }
                        }
                    }
                }
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| Payment Overview Toggle
|--------------------------------------------------------------------------
*/

const overviewToggle =
    document.getElementById(
        'overviewToggle'
    );

const paymentOverview =
    document.getElementById(
        'paymentOverview'
    );

if (
    overviewToggle &&
    paymentOverview
) {

    overviewToggle.addEventListener(
        'click',

        function() {

            const isOpen =
                paymentOverview.classList.contains(
                    'show'
                );

            if (isOpen) {

                paymentOverview.classList.remove(
                    'show'
                );

                overviewToggle.classList.remove(
                    'active'
                );

                overviewToggle.innerHTML =
                    'Payment Overview <span class="arrow">▼</span>';

            } else {

                paymentOverview.classList.add(
                    'show'
                );

                overviewToggle.classList.add(
                    'active'
                );

                overviewToggle.innerHTML =
                    'Hide Payment Overview <span class="arrow">▲</span>';
            }
        }
    );
}

</script>

</body>

</html>