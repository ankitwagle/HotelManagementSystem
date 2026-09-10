<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Reservation.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$db = Database::connect();
$reservationModel = new Reservation();

$rooms = $db->query("
    SELECT
        id,
        room_number,
        room_type,
        price,
        capacity,
        floor,
        status
    FROM rooms
    ORDER BY room_number ASC
")->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');

foreach ($rooms as &$room) {

    $reservations = $reservationModel->getRoomReservations((int)$room['id']);

    $currentBooking = null;
    $nextBooking = null;

    foreach ($reservations as $reservation) {

        if (
            in_array($reservation['status'], ['pending', 'confirmed'], true)
            && $reservation['check_in'] <= $today
            && $reservation['check_out'] > $today
        ) {
            $currentBooking = $reservation;
            break;
        }

        if (
            in_array($reservation['status'], ['pending', 'confirmed'], true)
            && $reservation['check_in'] > $today
        ) {
            $nextBooking = $reservation;
            break;
        }
    }

    if ($currentBooking) {

        $room['dynamic_status'] = 'Booked';
        $room['status_class'] = 'booked';

        $room['guest_name'] = $currentBooking['customer_name'] ?? 'Unknown';
        $room['guest_email'] = $currentBooking['customer_email'] ?? '-';

        $room['check_in'] = $currentBooking['check_in'];
        $room['check_out'] = $currentBooking['check_out'];

        $room['availability_info'] =
            'Booked until ' . date('M d, Y', strtotime($currentBooking['check_out']));

    } elseif ($nextBooking) {

        $room['dynamic_status'] = 'Booked Soon';
        $room['status_class'] = 'booked-soon';

        $room['guest_name'] = $nextBooking['customer_name'] ?? 'Unknown';
        $room['guest_email'] = $nextBooking['customer_email'] ?? '-';

        $room['check_in'] = $nextBooking['check_in'];
        $room['check_out'] = $nextBooking['check_out'];

        $room['availability_info'] =
            'Available until ' . date('M d, Y', strtotime($nextBooking['check_in']));

    } else {

        $room['dynamic_status'] = 'Available';
        $room['status_class'] = 'available';

        $room['guest_name'] = '-';
        $room['guest_email'] = '-';
        $room['check_in'] = '-';
        $room['check_out'] = '-';

        $room['availability_info'] = 'No upcoming booking';
    }
}

unset($room);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Rooms | LuxeStay Admin</title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .admin-page {
            max-width: 1450px;
            margin: 40px auto;
            padding: 20px;
        }

        .admin-page h1 {
            color: #102a4c;
        }

        .room-summary {
            margin-top: 25px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .summary-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        .summary-card h3 {
            margin: 0 0 8px;
            color: #102a4c;
        }

        .summary-number {
            font-size: 30px;
            font-weight: bold;
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
            min-width: 1200px;
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
            white-space: nowrap;
        }

        td {
            vertical-align: top;
        }

        .room-number {
            font-weight: bold;
            color: #102a4c;
            font-size: 17px;
        }

        .room-type {
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status.available {
            background: #dff5e5;
            color: #176b2c;
        }

        .status.booked {
            background: #f8d7da;
            color: #9b1c24;
        }

        .status.booked-soon {
            background: #fff0c2;
            color: #856404;
        }

        .guest-name {
            font-weight: bold;
        }

        .guest-email {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }

        .availability {
            font-weight: bold;
            color: #9b7418;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #9b7418;
            font-weight: bold;
            text-decoration: none;
        }

        @media (max-width: 800px) {

            .room-summary {
                grid-template-columns: 1fr;
            }

            .admin-page {
                margin: 20px auto;
                padding: 10px;
            }

        }

    </style>

</head>

<body>

<header>

    <div class="logo">

        <a
            href="/index.php"
            style="color:inherit;text-decoration:none;"
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


<main class="admin-page">

    <p class="eyebrow">
        ROOM MANAGEMENT
    </p>

    <h1>
        Hotel Rooms
    </h1>

    <p>
        View room availability, upcoming bookings, and current guests.
    </p>


    <?php

    $availableCount = 0;
    $bookedCount = 0;
    $bookedSoonCount = 0;

    foreach ($rooms as $room) {

        if ($room['dynamic_status'] === 'Available') {
            $availableCount++;
        }

        if ($room['dynamic_status'] === 'Booked') {
            $bookedCount++;
        }

        if ($room['dynamic_status'] === 'Booked Soon') {
            $bookedSoonCount++;
        }
    }

    ?>


    <div class="room-summary">

        <div class="summary-card">

            <h3>
                Available Now
            </h3>

            <div class="summary-number">
                <?= $availableCount ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>
                Currently Booked
            </h3>

            <div class="summary-number">
                <?= $bookedCount ?>
            </div>

        </div>


        <div class="summary-card">

            <h3>
                Booked Soon
            </h3>

            <div class="summary-number">
                <?= $bookedSoonCount ?>
            </div>

        </div>

    </div>


    <div class="table-container">

        <?php if (!$rooms): ?>

            <p>
                No rooms found.
            </p>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Room
                        </th>

                        <th>
                            Room Type
                        </th>

                        <th>
                            Price / Night
                        </th>

                        <th>
                            Capacity
                        </th>

                        <th>
                            Floor
                        </th>

                        <th>
                            Current Status
                        </th>

                        <th>
                            Guest
                        </th>

                        <th>
                            Check-in
                        </th>

                        <th>
                            Check-out
                        </th>

                        <th>
                            Availability
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($rooms as $room): ?>

                        <tr>

                            <td>

                                <div class="room-number">
                                    <?= htmlspecialchars($room['room_number']) ?>
                                </div>

                            </td>


                            <td>

                                <div class="room-type">
                                    <?= htmlspecialchars($room['room_type']) ?>
                                </div>

                            </td>


                            <td>

                                $<?= number_format((float)$room['price'], 2) ?>

                            </td>


                            <td>

                                <?= (int)$room['capacity'] ?>

                                guest(s)

                            </td>


                            <td>

                                <?= htmlspecialchars((string)$room['floor']) ?>

                            </td>


                            <td>

                                <span
                                    class="status <?= htmlspecialchars($room['status_class']) ?>"
                                >

                                    <?= htmlspecialchars($room['dynamic_status']) ?>

                                </span>

                            </td>


                            <td>

                                <?php if ($room['guest_name'] !== '-'): ?>

                                    <div class="guest-name">

                                        <?= htmlspecialchars($room['guest_name']) ?>

                                    </div>

                                    <div class="guest-email">

                                        <?= htmlspecialchars($room['guest_email']) ?>

                                    </div>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if ($room['check_in'] !== '-'): ?>

                                    <?= date(
                                        'M d, Y',
                                        strtotime($room['check_in'])
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if ($room['check_out'] !== '-'): ?>

                                    <?= date(
                                        'M d, Y',
                                        strtotime($room['check_out'])
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="availability">

                                    <?= htmlspecialchars($room['availability_info']) ?>

                                </div>

                            </td>

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