
<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin access required.');
}

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| Basic Statistics
|--------------------------------------------------------------------------
*/

$totalCustomers = (int) $db
    ->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")
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

$approvedReservations = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'approved'
    ")
    ->fetchColumn();

$rejectedReservations = (int) $db
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'rejected'
    ")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| Reservation Status Report
|--------------------------------------------------------------------------
*/

$statusReport = $db->query("
    SELECT status, COUNT(*) AS total
    FROM reservations
    GROUP BY status
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Monthly Reservation Report
|--------------------------------------------------------------------------
*/

$monthlyReport = $db->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        COUNT(*) AS total
    FROM reservations
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

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
        Reports | LuxeStay Admin
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .admin-page {
            max-width: 1300px;
            margin: 40px auto;
            padding: 20px;
        }

        .admin-page h1 {
            color: #102a4c;
        }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .report-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        .report-card h2 {
            font-size: 32px;
            margin: 10px 0;
            color: #102a4c;
        }

        .report-section {
            margin-top: 35px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #102a4c;
            color: white;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            color: #9b7418;
            font-weight: bold;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .report-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .report-cards {
                grid-template-columns: 1fr;
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
            Dashboard
        </a>

        <a href="/views/admin/reservations.php">
            Reservations
        </a>

        <a href="/views/admin/customers.php">
            Customers
        </a>

        <a href="/views/admin/rooms.php">
            Rooms
        </a>

        <a href="/views/admin/reports.php">
            Reports
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main class="admin-page">

    <p class="eyebrow">
        HOTEL REPORTING
    </p>

    <h1>
        Reports
    </h1>

    <p>
        View overall hotel activity and reservation statistics.
    </p>


    <!-- SUMMARY -->

    <section class="report-cards">

        <article class="report-card">

            <p class="eyebrow">
                CUSTOMERS
            </p>

            <h2>
                <?= $totalCustomers ?>
            </h2>

            <p>
                Registered customers
            </p>

        </article>


        <article class="report-card">

            <p class="eyebrow">
                ROOMS
            </p>

            <h2>
                <?= $totalRooms ?>
            </h2>

            <p>
                Rooms in the system
            </p>

        </article>


        <article class="report-card">

            <p class="eyebrow">
                RESERVATIONS
            </p>

            <h2>
                <?= $totalReservations ?>
            </h2>

            <p>
                Total reservations
            </p>

        </article>


        <article class="report-card">

            <p class="eyebrow">
                PENDING
            </p>

            <h2>
                <?= $pendingReservations ?>
            </h2>

            <p>
                Reservations requiring attention
            </p>

        </article>

    </section>


    <!-- RESERVATION STATUS -->

    <section class="report-section">

        <p class="eyebrow">
            RESERVATION ANALYSIS
        </p>

        <h2>
            Reservation Status
        </h2>

        <table>

            <thead>

                <tr>
                    <th>Status</th>
                    <th>Total Reservations</th>
                </tr>

            </thead>

            <tbody>

            <?php if (!$statusReport): ?>

                <tr>
                    <td colspan="2">
                        No reservation data available.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($statusReport as $row): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($row['status']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['total']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </section>


    <!-- MONTHLY REPORT -->

    <section class="report-section">

        <p class="eyebrow">
            RESERVATION TREND
        </p>

        <h2>
            Reservations by Month
        </h2>

        <table>

            <thead>

                <tr>
                    <th>Month</th>
                    <th>Total Reservations</th>
                </tr>

            </thead>

            <tbody>

            <?php if (!$monthlyReport): ?>

                <tr>
                    <td colspan="2">
                        No monthly reservation data available.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($monthlyReport as $row): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($row['month']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['total']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </section>


    <a
        class="back"
        href="/views/admin/index.php"
    >
        ← Back to Admin Dashboard
    </a>

</main>

</body>

</html>

