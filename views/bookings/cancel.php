<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';

requireLogin();

$userId =
    (int) ($_SESSION['user']['id'] ?? 0);

$reservationId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Cancel Reservation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservationId =
        (int) ($_POST['reservation_id'] ?? 0);

    if ($reservationId > 0) {

        $reservationController =
            new ReservationController();

        $result =
            $reservationController->cancel(
                $reservationId,
                $userId
            );

        if ($result['success']) {

            header(
                'Location: /views/bookings/index.php?message=' .
                urlencode(
                    'Reservation cancelled successfully.'
                )
            );

            exit;

        } else {

            $message =
                $result['message']
                ?? 'Unable to cancel reservation.';

            $messageType = 'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Reservation Details
|--------------------------------------------------------------------------
*/

$reservation = null;

if ($reservationId > 0) {

    $db = Database::connect();

    $stmt = $db->prepare("
        SELECT
            r.*,
            rm.room_type AS room_name,
            rm.price
        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE r.id = ?
          AND r.user_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $reservationId,
        $userId
    ]);

    $reservation =
        $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Invalid Reservation
|--------------------------------------------------------------------------
*/

if (!$reservation) {

    header(
        'Location: /views/bookings/index.php'
    );

    exit;
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
    Reservation Details | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .reservation-message {
        max-width: 900px;
        margin: 20px auto;
        padding: 15px;
        border-radius: 8px;
        font-weight: 600;
    }

    .reservation-message.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .cancel-button {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        background: #dc2626;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        font-size: 15px;
    }

    .cancel-button:hover {
        background: #b91c1c;
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

        <a href="/views/rooms/index.php">
            Rooms
        </a>

        <a href="/views/bookings/index.php">
            My Reservations
        </a>

        <a href="/views/payments/index.php">
            Payments
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


<main>


<section class="welcome">

    <p class="eyebrow">
        LUXESTAY RESERVATION
    </p>

    <h1>
        Reservation #<?= (int) $reservation['id'] ?>
    </h1>

    <p>
        Here are the complete details of your reservation.
    </p>

</section>


<?php if ($message !== ''): ?>

    <div
        class="reservation-message <?= htmlspecialchars($messageType) ?>"
    >

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<section class="welcome">

    <div class="cards">


        <article>

            <p class="eyebrow">
                ROOM
            </p>

            <h2>
                <?= htmlspecialchars(
                    $reservation['room_name'] ?? 'Room'
                ) ?>
            </h2>

            <p>

                <strong>
                    Price:
                </strong>

                $<?= htmlspecialchars(
                    number_format(
                        (float) $reservation['price'],
                        2
                    )
                ) ?>

                / night

            </p>

        </article>


        <article>

            <p class="eyebrow">
                STAY DETAILS
            </p>

            <p>

                <strong>
                    Check-in:
                </strong>

                <?= htmlspecialchars(
                    $reservation['check_in']
                ) ?>

            </p>

            <p>

                <strong>
                    Check-out:
                </strong>

                <?= htmlspecialchars(
                    $reservation['check_out']
                ) ?>

            </p>

            <p>

                <strong>
                    Guests:
                </strong>

                <?= (int) $reservation['guests'] ?>

            </p>

        </article>


        <article>

            <p class="eyebrow">
                RESERVATION STATUS
            </p>

            <h2>
                <?= htmlspecialchars(
                    ucfirst($reservation['status'])
                ) ?>
            </h2>

            <p>

                <strong>
                    Booked On:
                </strong>

                <?= htmlspecialchars(
                    $reservation['created_at']
                ) ?>

            </p>

        </article>


        <?php if (!empty($reservation['special_requests'])): ?>

            <article>

                <p class="eyebrow">
                    SPECIAL REQUESTS
                </p>

                <p>

                    <?= htmlspecialchars(
                        $reservation['special_requests']
                    ) ?>

                </p>

            </article>

        <?php endif; ?>


    </div>


    <div class="hero-buttons">

        <a href="/views/bookings/index.php">
            ← Back to My Reservations
        </a>


        <?php if ($reservation['status'] === 'pending'): ?>

            <form
                method="POST"
                style="display: inline;"
                onsubmit="
                    return confirm(
                        'Are you sure you want to cancel this reservation?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="reservation_id"
                    value="<?= (int) $reservation['id'] ?>"
                >

                <button
                    type="submit"
                    class="cancel-button"
                >
                    Cancel Reservation
                </button>

            </form>

        <?php elseif ($reservation['status'] === 'cancelled'): ?>

            <p>
                This reservation has already been cancelled.
            </p>

        <?php elseif ($reservation['status'] === 'confirmed'): ?>

            <p>
                This reservation has been confirmed and can no longer
                be cancelled from this page.
            </p>

        <?php endif; ?>


    </div>

</section>


</main>


<footer>

<div>

    <strong>
        LuxeStay
    </strong>

    <p>
        Excellence in hospitality and refined comfort.
    </p>

</div>

</footer>

</body>

</html>