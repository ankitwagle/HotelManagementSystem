<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ReservationController.php';

requireLogin();

$error = '';
$success = '';

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| Get Room Types
|--------------------------------------------------------------------------
|
| We show room TYPES to customers.
| The system automatically assigns a physical room.
|
*/

$rooms = $db->query("
    SELECT
        room_type,
        MIN(price) AS price,
        MIN(capacity) AS capacity
    FROM rooms
    WHERE status = 'available'
    GROUP BY room_type
    ORDER BY MIN(price) ASC
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Selected Room Type
|--------------------------------------------------------------------------
*/

$roomType = trim(
    $_GET['room_type']
    ?? $_POST['room_type']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| Validate Selected Room Type
|--------------------------------------------------------------------------
*/

$selectedRoom = null;

foreach ($rooms as $room) {

    if ($room['room_type'] === $roomType) {
        $selectedRoom = $room;
        break;
    }
}

if ($roomType !== '' && !$selectedRoom) {
    $error = 'Please select a valid room type.';
}


/*
|--------------------------------------------------------------------------
| Handle Reservation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 1);
    $specialRequests = trim(
        $_POST['special_requests'] ?? ''
    );

    $controller = new ReservationController();

    $result = $controller->create(
        (int) $_SESSION['user']['id'],
        $roomType,
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
                <?= htmlspecialchars($selectedRoom['room_type']) ?>
                ·
                $<?= number_format(
                    (float) $selectedRoom['price'],
                    2
                ) ?>
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


                <!-- ROOM TYPE -->

                <label for="room_type">
                    Room Type
                </label>

                <select
                    id="room_type"
                    name="room_type"
                    required
                >

                    <option value="">
                        Select a room type
                    </option>

                    <?php foreach ($rooms as $room): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $room['room_type']
                            ) ?>"
                            <?= $roomType === $room['room_type']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $room['room_type']
                            ) ?>

                            -
                            $<?= number_format(
                                (float) $room['price'],
                                2
                            ) ?>
                            / night

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- CHECK IN -->

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


                <!-- CHECK OUT -->

                <label for="check_out">
                    Check-out Date
                </label>

                <input
                    id="check_out"
                    type="date"
                    name="check_out"
                    min="<?= date(
                        'Y-m-d',
                        strtotime('+1 day')
                    ) ?>"
                    required
                >


                <!-- GUESTS -->

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


                <!-- SPECIAL REQUESTS -->

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