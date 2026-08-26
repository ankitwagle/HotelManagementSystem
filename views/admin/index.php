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
|
| Revenue comes only from actual paid payment records.
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
| Monthly Payment Revenue
|--------------------------------------------------------------------------
*/

$chartLabels = [];
$chartRevenue = [];

try {

    $chartStmt = $db->query("
        SELECT
            DATE_FORMAT(paid_at, '%b %Y') AS payment_month,
            YEAR(paid_at) AS payment_year,
            MONTH(paid_at) AS payment_month_number,
            COALESCE(SUM(amount), 0) AS revenue

        FROM payments

        WHERE LOWER(status) = 'paid'
        AND paid_at IS NOT NULL

        GROUP BY
            YEAR(paid_at),
            MONTH(paid_at)

        ORDER BY
            YEAR(paid_at),
            MONTH(paid_at)
    ");

    $chartRows = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($chartRows as $row) {

        $chartLabels[] = $row['payment_month'];
        $chartRevenue[] = (float) $row['revenue'];
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

        .payment-analytics-header {
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
        🛏 LuxeStay Admin
    </a>

</div>

<nav>

    <a href="/index.php">
        Home
    </a>

    <a href="/views/admin/index.php">
        Admin Dashboard
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


    <!-- PAYMENT CHART IS VISIBLE BY DEFAULT -->

    <article class="payment-chart-card">

        <p class="eyebrow">
            REVENUE TREND
        </p>

        <h2>
            Monthly Paid Revenue
        </h2>

        <p>
            This chart uses actual successful payment records.
        </p>

        <div class="chart-wrapper">

            <canvas
                id="paymentRevenueChart"
            ></canvas>

        </div>

    </article>


    <!-- PAYMENT OVERVIEW IS HIDDEN BY DEFAULT -->

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

            <a href="/views/admin/notifications.php">
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
