<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$db = Database::connect();

$rooms = $db->query("
    SELECT *
    FROM rooms
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$columns = [];

if (!empty($rooms)) {
    $columns = array_keys($rooms[0]);
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
        Rooms | LuxeStay Admin
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

        .table-container {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
            margin-top: 20px;
            color: #9b7418;
            font-weight: bold;
            text-decoration: none;
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

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main class="admin-page">

    <p class="eyebrow">
        ROOM MANAGEMENT
    </p>

    <h1>
        Hotel Rooms
    </h1>

    <p>
        View all rooms and their current information.
    </p>


    <div class="table-container">

        <?php if (!$rooms): ?>

            <p>
                No rooms found.
            </p>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <?php foreach ($columns as $column): ?>

                            <th>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $column))) ?>
                            </th>

                        <?php endforeach; ?>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($rooms as $room): ?>

                        <tr>

                            <?php foreach ($columns as $column): ?>

                                <td>

                                    <?= htmlspecialchars(
                                        (string)($room[$column] ?? '-')
                                    ) ?>

                                </td>

                            <?php endforeach; ?>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>


    <a
        class="back"
        href="/views/admin/index.php"
    >
        ← Back to Admin Dashboard
    </a>

</main>

</body>

</html>