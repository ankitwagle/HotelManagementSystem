<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user']['id'];

$db = Database::connect();

$stmt = $db->prepare("
    SELECT
        r.id,
        r.room_id,
        r.check_in,
        r.check_out,
        r.guests,
        r.status,
        r.created_at,
        rm.room_type AS room_name,
        rm.price
    FROM reservations r
    INNER JOIN rooms rm
        ON r.room_id = rm.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");

$stmt->execute([$userId]);

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
        Payments | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

</head>

<body>

<header>

    <div class="logo">
        🛏 LuxeStay
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

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main>

    <section class="welcome">

        <p class="eyebrow">
            LUXESTAY PAYMENTS
        </p>

        <h1>
            Payments
        </h1>

        <p>
            View the payment information for your reservations.
        </p>

    </section>


    <section class="welcome">

        <?php if (empty($reservations)): ?>

            <article>

                <h2>
                    No Reservations Found
                </h2>

                <p>
                    You need to make a reservation before making a payment.
                </p>

                <a href="/views/rooms/index.php">
                    Explore Rooms →
                </a>

            </article>

        <?php else: ?>

            <div class="cards">

                <?php foreach ($reservations as $reservation): ?>

                    <?php

                    $checkIn = new DateTime(
                        $reservation['check_in']
                    );

                    $checkOut = new DateTime(
                        $reservation['check_out']
                    );

                    $nights = $checkIn->diff($checkOut)->days;

                    if ($nights < 1) {
                        $nights = 1;
                    }

                    $total = $nights * (float) $reservation['price'];

                    ?>

                    <article>

                        <p class="eyebrow">
                            RESERVATION
                            #<?= (int) $reservation['id'] ?>
                        </p>

                        <h2>
                            <?= htmlspecialchars(
                                $reservation['room_name']
                            ) ?>
                        </h2>

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
                                Number of nights:
                            </strong>

                            <?= $nights ?>

                        </p>

                        <p>

                            <strong>
                                Guests:
                            </strong>

                            <?= (int) $reservation['guests'] ?>

                        </p>

                        <p>

                            <strong>
                                Price per night:
                            </strong>

                            $<?= number_format(
                                (float) $reservation['price'],
                                2
                            ) ?>

                        </p>

                        <p>

                            <strong>
                                Total Amount:
                            </strong>

                            $<?= number_format(
                                $total,
                                2
                            ) ?>

                        </p>

                        <p>

                            <strong>
                                Reservation Status:
                            </strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $reservation['status']
                                )
                            ) ?>

                        </p>

                        <?php if (
                            $reservation['status'] === 'approved'
                        ): ?>

                            <div class="hero-buttons">

                                <a href="#">
                                    Proceed to Payment →
                                </a>

                            </div>

                        <?php elseif (
                            $reservation['status'] === 'pending'
                        ): ?>

                            <p>
                                Payment will be available after your
                                reservation is approved.
                            </p>

                        <?php elseif (
                            $reservation['status'] === 'cancelled'
                        ): ?>

                            <p>
                                This reservation has been cancelled.
                            </p>

                        <?php else: ?>

                            <p>
                                Payment is not currently available
                                for this reservation.
                            </p>

                        <?php endif; ?>

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