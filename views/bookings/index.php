<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Reservation.php';

requireLogin();

$reservationModel = new Reservation();

$userId = (int) $_SESSION['user']['id'];

$reservations = $reservationModel->getUserReservations($userId);

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
    My Reservations | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>


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
        LUXESTAY
    </p>

    <h1>
        My Reservations
    </h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user']['name']) ?>.
        Here you can view your reservations and their current status.
    </p>

    <div class="hero-buttons">

        <a href="/views/rooms/index.php">
            Reserve Another Room →
        </a>

    </div>

</section>


<section class="welcome">

    <?php if (empty($reservations)): ?>

        <article>

            <h2>
                No Reservations Yet
            </h2>

            <p>
                You have not made any reservations yet.
            </p>

            <a href="/views/rooms/index.php">
                Explore Rooms →
            </a>

        </article>

    <?php else: ?>

        <div class="cards">

            <?php foreach ($reservations as $reservation): ?>

                <article>

                    <p class="eyebrow">
                        RESERVATION #<?= (int) $reservation['id'] ?>
                    </p>

                    <h2>
                        <?= htmlspecialchars(
                            $reservation['room_name'] ?? 'Room'
                        ) ?>
                    </h2>

                    <p>
                        <strong>
                            Check-in:
                        </strong>

                        <?= htmlspecialchars(
                            $reservation['check_in'] ?? ''
                        ) ?>
                    </p>

                    <p>
                        <strong>
                            Check-out:
                        </strong>

                        <?= htmlspecialchars(
                            $reservation['check_out'] ?? ''
                        ) ?>
                    </p>

                    <p>
                        <strong>
                            Guests:
                        </strong>

                        <?= (int) ($reservation['guests'] ?? 0) ?>
                    </p>

                    <?php if (!empty($reservation['special_requests'])): ?>

                        <p>
                            <strong>
                                Special Requests:
                            </strong>

                            <?= htmlspecialchars(
                                $reservation['special_requests']
                            ) ?>
                        </p>

                    <?php endif; ?>

                    <p>
                        <strong>
                            Status:
                        </strong>

                        <?= htmlspecialchars(
                            ucfirst($reservation['status'] ?? 'pending')
                        ) ?>
                    </p>

                    <p>
                        <strong>
                            Booked On:
                        </strong>

                        <?= htmlspecialchars(
                            $reservation['created_at'] ?? ''
                        ) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

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
