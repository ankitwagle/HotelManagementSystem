
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

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| GET COMPLETE RESERVATION HISTORY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We intentionally DO NOT use:
|
| WHERE deleted_at IS NULL
|
| This means cancelled/soft-deleted reservations remain visible
| in the admin reservation history.
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        r.id,
        r.user_id,
        r.room_id,
        r.check_in,
        r.check_out,
        r.guests,
        r.special_requests,
        r.status,
        r.created_at,
        r.deleted_at,

        u.name AS customer_name,
        u.email AS customer_email,

        rm.room_type AS room_name,
        rm.price AS room_price

    FROM reservations r

    LEFT JOIN users u
        ON r.user_id = u.id

    LEFT JOIN rooms rm
        ON r.room_id = rm.id

    ORDER BY r.created_at DESC
");

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        All Reservations | LuxeStay Admin
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .admin-container {
            width: 95%;
            max-width: 1400px;
            margin: 40px auto;
        }

        .admin-header {
            margin-bottom: 30px;
        }

        .admin-header h1 {
            margin-bottom: 8px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
        }

        .history-note {
            margin-top: 20px;
            padding: 15px 18px;
            background: #f5f1e8;
            border-radius: 8px;
            color: #5f4a1d;
        }

        .table-wrapper {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f7f7f7;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:hover {
            background: #fafafa;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.approved {
            background: #d4edda;
            color: #155724;
        }

        .status.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status.cancelled {
            background: #e2e3e5;
            color: #383d41;
        }

        .archived {
            background: #f1f1f1;
            color: #666;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 50px;
            color: #777;
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

        <a href="/views/admin/rooms.php">
            Rooms
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


<main class="admin-container">

    <a
        class="back-link"
        href="/views/admin/index.php"
    >
        ← Back to Admin Dashboard
    </a>


    <section class="admin-header">

        <p class="eyebrow">
            LUXESTAY ADMINISTRATION
        </p>

        <h1>
            All Reservations
        </h1>

        <p>
            View the complete reservation history of the hotel system.
        </p>

        <div class="history-note">

            <strong>
                Reservation History:
            </strong>

            Cancelled or archived reservations remain visible here
            for reporting and historical records.

        </div>

    </section>


    <section class="table-wrapper">

        <?php if (empty($reservations)): ?>

            <div class="empty">

                <h2>
                    No reservations found
                </h2>

                <p>
                    There are currently no reservations in the system.
                </p>

            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Email
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
                            Guests
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                        <th>
                            History
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($reservations as $reservation): ?>

                        <tr>

                            <td>
                                #<?= (int) $reservation['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['customer_name'] ?? 'Unknown'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['customer_email'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['room_name'] ?? 'Unknown Room'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['check_in']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['check_out']
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $reservation['guests'] ?>
                            </td>

                            <td>
                                $<?= number_format(
                                    (float) ($reservation['room_price'] ?? 0),
                                    2
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="status <?= htmlspecialchars(
                                        strtolower(
                                            $reservation['status'] ?? 'pending'
                                        )
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $reservation['status'] ?? 'pending'
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation['created_at']
                                ) ?>
                            </td>

                            <td>

                                <?php if (!empty($reservation['deleted_at'])): ?>

                                    <span class="archived">
                                        Archived
                                    </span>

                                <?php else: ?>

                                    Active

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </section>

</main>


<footer>

    <strong>
        LuxeStay
    </strong>

    <p>
        Hotel Management Administration System.
    </p>

</footer>

</body>

</html>

