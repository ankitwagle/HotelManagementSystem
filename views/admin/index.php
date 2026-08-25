<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
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

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| BASIC DASHBOARD DATA
|--------------------------------------------------------------------------
*/

$totalUsers = (int) $db
    ->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")
    ->fetchColumn();

$totalRooms = (int) $db
    ->query("SELECT COUNT(*) FROM rooms")
    ->fetchColumn();

$totalReservations = (int) $db
    ->query("SELECT COUNT(*) FROM reservations")
    ->fetchColumn();

$pendingReservations = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'pending'
    ")
    ->fetchColumn();

$todayBookings = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE DATE(created_at) = CURDATE()
    ")
    ->fetchColumn();

$checkInsToday = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE DATE(check_in) = CURDATE()
        AND status IN ('pending', 'approved')
    ")
    ->fetchColumn();

$checkOutsToday = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE DATE(check_out) = CURDATE()
        AND status IN ('pending', 'approved')
    ")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| RESERVATION STATUS GRAPH
|--------------------------------------------------------------------------
*/

$statusRows = $db
    ->query("
        SELECT status, COUNT(*) AS total
        FROM reservations
        GROUP BY status
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [];
$statusValues = [];

foreach ($statusRows as $row) {
    $statusLabels[] = ucfirst($row['status']);
    $statusValues[] = (int) $row['total'];
}

/*
|--------------------------------------------------------------------------
| MONTHLY RESERVATION GRAPH
|--------------------------------------------------------------------------
*/

$monthlyRows = $db
    ->query("
        SELECT
            DATE_FORMAT(created_at, '%b') AS month_name,
            COUNT(*) AS total
        FROM reservations
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%b')
        ORDER BY YEAR(created_at), MONTH(created_at)
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

$monthlyLabels = [];
$monthlyValues = [];

foreach ($monthlyRows as $row) {
    $monthlyLabels[] = $row['month_name'];
    $monthlyValues[] = (int) $row['total'];
}

/*
|--------------------------------------------------------------------------
| CHECK-IN / CHECK-OUT GRAPH
|--------------------------------------------------------------------------
*/

$movementLabels = ['Check-ins Today', 'Check-outs Today'];
$movementValues = [$checkInsToday, $checkOutsToday];

/*
|--------------------------------------------------------------------------
| DATE
|--------------------------------------------------------------------------
*/

$today = date('l, F j, Y');

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
        Admin Dashboard | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        .admin-dashboard {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 25px 70px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .admin-header .eyebrow {
            letter-spacing: 3px;
            font-size: 12px;
            font-weight: bold;
            color: #9b7418;
        }

        .admin-header h1 {
            margin: 10px 0;
            font-size: 42px;
        }

        .admin-header p {
            margin: 5px 0;
        }

        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-top: 4px solid #9b7418;
        }

        .stat-card .label {
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 12px;
        }

        .stat-card .number {
            font-size: 34px;
            font-weight: bold;
            color: #102a4c;
        }

        .stat-card p {
            margin-top: 8px;
            color: #666;
        }

        /*
        |--------------------------------------------------------------------------
        | CHARTS
        |--------------------------------------------------------------------------
        */

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .chart-card h2 {
            font-size: 20px;
            margin-bottom: 25px;
            color: #102a4c;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /*
        |--------------------------------------------------------------------------
        | MANAGEMENT LINKS
        |--------------------------------------------------------------------------
        */

        .management-section {
            margin-top: 35px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .management-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .management-card h3 {
            color: #102a4c;
            margin-bottom: 10px;
        }

        .management-card a {
            color: #9b7418;
            font-weight: bold;
            text-decoration: none;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .management-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .stats-grid,
            .management-grid {
                grid-template-columns: 1fr;
            }

            .admin-header h1 {
                font-size: 32px;
            }

        }

    </style>

</head>

<body>

<header>

    <div class="logo">
        🛏 LuxeStay Admin
    </div>

    <nav>

        <a href="/views/admin/index.php">
            Admin Dashboard
        </a>

        <a href="/views/admin/reservations.php">
            All Reservations
        </a>

        <a href="/views/admin/customers.php">
            Customers
        </a>

        <a href="/views/admin/payments.php">
            Customer Cash Flow
        </a>

        <a href="/views/admin/reports.php">
            Reports
        </a>

        <a href="/index.php">
            Hotel Home
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main class="admin-dashboard">

    <!-- HEADER -->

    <section class="admin-header">

        <p class="eyebrow">
            LUXESTAY ADMINISTRATION
        </p>

        <h1>
            Admin Dashboard
        </h1>

        <p>
            Welcome,
            <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Administrator') ?>
        </p>

        <p>
            <?= htmlspecialchars($today) ?>
        </p>

    </section>


    <!-- STATISTICS -->

    <section class="stats-grid">

        <article class="stat-card">

            <div class="label">
                Today's Bookings
            </div>

            <div class="number">
                <?= $todayBookings ?>
            </div>

            <p>
                New reservations today.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Total Reservations
            </div>

            <div class="number">
                <?= $totalReservations ?>
            </div>

            <p>
                All reservations.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Pending
            </div>

            <div class="number">
                <?= $pendingReservations ?>
            </div>

            <p>
                Require attention.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Customers
            </div>

            <div class="number">
                <?= $totalUsers ?>
            </div>

            <p>
                Registered customers.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Rooms
            </div>

            <div class="number">
                <?= $totalRooms ?>
            </div>

            <p>
                Rooms in the system.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Check-ins Today
            </div>

            <div class="number">
                <?= $checkInsToday ?>
            </div>

            <p>
                Guests arriving today.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Check-outs Today
            </div>

            <div class="number">
                <?= $checkOutsToday ?>
            </div>

            <p>
                Guests departing today.
            </p>

        </article>


        <article class="stat-card">

            <div class="label">
                Admin System
            </div>

            <div class="number">
                Active
            </div>

            <p>
                Hotel management system.
            </p>

        </article>

    </section>


    <!-- GRAPHS -->

    <section class="charts-grid">

        <!-- GRAPH 1 -->

        <article class="chart-card">

            <h2>
                Reservation Status
            </h2>

            <div class="chart-container">

                <canvas id="reservationStatusChart"></canvas>

            </div>

        </article>


        <!-- GRAPH 2 -->

        <article class="chart-card">

            <h2>
                Reservations by Month
            </h2>

            <div class="chart-container">

                <canvas id="monthlyReservationsChart"></canvas>

            </div>

        </article>


        <!-- GRAPH 3 -->

        <article class="chart-card">

            <h2>
                Today's Check-ins vs Check-outs
            </h2>

            <div class="chart-container">

                <canvas id="movementChart"></canvas>

            </div>

        </article>

    </section>


    <!-- MANAGEMENT -->

    <section class="management-section">

        <div class="management-grid">

            <article class="management-card">

                <h3>
                    Admin Dashboard
                </h3>

                <p>
                    View today's hotel activity and statistics.
                </p>

                <a href="/views/admin/index.php">
                    Open Dashboard →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    All Reservations
                </h3>

                <p>
                    View and manage every customer reservation.
                </p>

                <a href="/views/admin/reservations.php">
                    View Reservations →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    Customers
                </h3>

                <p>
                    View registered hotel customers.
                </p>

                <a href="/views/admin/customers.php">
                    View Customers →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    Customer Cash Flow
                </h3>

                <p>
                    View payments, revenue and refund requests.
                </p>

                <a href="/views/admin/payments.php">
                    View Cash Flow →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    Reports
                </h3>

                <p>
                    View detailed hotel reports.
                </p>

                <a href="/views/admin/reports.php">
                    View Reports →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    Rooms
                </h3>

                <p>
                    Manage hotel rooms and availability.
                </p>

                <a href="/views/admin/rooms.php">
                    Manage Rooms →
                </a>

            </article>


            <article class="management-card">

                <h3>
                    Notifications
                </h3>

                <p>
                    Manage system notifications.
                </p>

                <a href="/views/admin/notifications.php">
                    View Notifications →
                </a>

            </article>

        </div>

    </section>

</main>


<script>

    /*
    |--------------------------------------------------------------------------
    | RESERVATION STATUS - DOUGHNUT
    |--------------------------------------------------------------------------
    */

    new Chart(
        document.getElementById('reservationStatusChart'),
        {
            type: 'doughnut',

            data: {
                labels: <?= json_encode($statusLabels) ?>,

                datasets: [{
                    data: <?= json_encode($statusValues) ?>,

                    backgroundColor: [
                        '#649d1f',
                        '#e5a000',
                        '#d64545',
                        '#777',
                        '#3568a8'
                    ],

                    borderWidth: 2
                }]
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | MONTHLY RESERVATIONS - BAR
    |--------------------------------------------------------------------------
    */

    new Chart(
        document.getElementById('monthlyReservationsChart'),
        {
            type: 'bar',

            data: {

                labels: <?= json_encode($monthlyLabels) ?>,

                datasets: [{
                    label: 'Reservations',

                    data: <?= json_encode($monthlyValues) ?>,

                    backgroundColor: '#54c04c',

                    borderRadius: 5
                }]
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {
                        beginAtZero: true,

                        ticks: {
                            precision: 0
                        }
                    }

                },

                plugins: {

                    legend: {
                        display: false
                    }

                }

            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHECK-IN / CHECK-OUT - DOUGHNUT
    |--------------------------------------------------------------------------
    */

    new Chart(
        document.getElementById('movementChart'),
        {
            type: 'doughnut',

            data: {

                labels: <?= json_encode($movementLabels) ?>,

                datasets: [{

                    data: <?= json_encode($movementValues) ?>,

                    backgroundColor: [
                        '#59ed99',
                        '#d64545'
                    ],

                    borderWidth: 2

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        position: 'bottom'
                    }

                }

            }

        }
    );

</script>

</body>

</html>