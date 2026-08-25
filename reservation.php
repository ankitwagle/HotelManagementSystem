<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/ReservationController.php';

requireLogin();

$error = '';
$success = '';

$roomId = (int) ($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

$rooms = [
    1 => [
        'name' => 'Standard Room',
        'price' => 150
    ],
    2 => [
        'name' => 'Deluxe Room',
        'price' => 285
    ],
    3 => [
        'name' => 'Executive Suite',
        'price' => 450
    ]
];

if (!isset($rooms[$roomId])) {
    $error = 'Please select a valid room.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 1);
    $specialRequests = trim($_POST['special_requests'] ?? '');

    $controller = new ReservationController();

    $result = $controller->create(
        (int) $_SESSION['user']['id'],
        $roomId,
        $checkIn,
        $checkOut,
        $guests,
        $specialRequests
    );

    if ($result['success']) {

        $success = $result['message'];

    } else {

        $error = $result['message'];
    }
}

$selectedRoom = $rooms[$roomId] ?? null;

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
        Make a Reservation | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/auth.css"
    >

</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>
            Make a Reservation
        </h1>

        <?php if ($selectedRoom): ?>

            <p>
                <?= htmlspecialchars($selectedRoom['name']) ?>
                ·
                $<?= number_format($selectedRoom['price'], 2) ?>
                / night
            </p>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="success">
                <?= htmlspecialchars($success) ?>

                <br><br>

                <a href="index.php">
                    Return to Homepage →
                </a>
            </div>

        <?php else: ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="room_id"
                    value="<?= $roomId ?>"
                >

                <label for="check_in">
                    Check-in Date
                </label>

                <input
                    id="check_in"
                    type="date"
                    name="check_in"
                    min="<?= date('Y-m-d') ?>"
                    required
                >

                <label for="check_out">
                    Check-out Date
                </label>

                <input
                    id="check_out"
                    type="date"
                    name="check_out"
                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                    required
                >

                <label for="guests">
                    Number of Guests
                </label>

                <input
                    id="guests"
                    type="number"
                    name="guests"
                    min="1"
                    max="10"
                    value="1"
                    required
                >

                <label for="special_requests">
                    Special Requests
                </label>

                <textarea
                    id="special_requests"
                    name="special_requests"
                    placeholder="Any special requests?"
                ></textarea>

                <button type="submit">
                    Confirm Reservation →
                </button>

            </form>

        <?php endif; ?>

        <p class="bottom-link">

            <a href="views/rooms/index.php">
                ← Back to Rooms
            </a>

        </p>

    </div>

</div>

</body>

</html>