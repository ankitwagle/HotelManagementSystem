<?php

require_once __DIR__ . '/../../config/config.php';

requireLogin();

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
    Rooms | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<link
    rel="stylesheet"
    href="/public/css/customer.css"
>
</head>

<body class="luxury-rooms customer-ui">


<header class="site-header">

    <div class="logo">

        <a href="/index.php" aria-label="LuxeStay home">
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

    </div>


    <nav>

        <a href="/index.php">
            Home
        </a>

        <a
            class="active"
            href="/views/rooms/index.php"
        >
            Rooms
        </a>

        <a href="/views/bookings/index.php">
            My Reservations
        </a>

        <a href="/views/payments/index.php">
            Payments
        </a>

        <a href="/views/notifications/index.php">
            Notifications<?php require __DIR__ . '/../partials/notification-badge.php'; ?>
        </a>

        <a href="/views/customers/profile.php">
            Profile
        </a>

        <a
            class="nav-cta"
            href="/logout.php"
        >
            Logout
        </a>

    </nav>

</header>


<main>


    <!-- ROOMS HERO -->

    <section class="rooms-hero">

        <div>

            <p class="eyebrow">
                LUXESTAY ACCOMMODATION
            </p>

            <h1>
                Choose your <em>room.</em>
            </h1>

            <p>
                Welcome,
                <?= htmlspecialchars($_SESSION['user']['name']) ?>.
                Discover a stay designed around comfort,
                calm, and convenience.
            </p>

        </div>

    </section>


    <!-- ROOMS LIST -->

    <section class="rooms-list-section">

        <div class="room-list-heading">

            <div>

                <p class="eyebrow">
                    STAY YOUR WAY
                </p>

                <h2>
                    Find your perfect space
                </h2>

            </div>

            <p>
                All rates are shown per night.
                Select a room to continue to the
                secure reservation process.
            </p>

        </div>


        <div class="cards room-list-grid">


            <!-- STANDARD ROOM -->

            <article class="room-card room-standard">

                <div class="room-image">

                    <span class="room-tag">
                        COMFORT
                    </span>

                </div>


                <div class="room-card-body">

                    <div class="room-card-top">

                        <h2>
                            Standard Room
                        </h2>

                        <span class="room-price">
                            $150
                            <small>/ night</small>
                        </span>

                    </div>


                    <p>
                        Comfortable accommodation with
                        everything you need for a relaxing stay.
                    </p>


                    <div class="room-meta">

                        <span>
                            ♙ 2 guests
                        </span>

                        <span>
                            ◷ Flexible stay
                        </span>

                    </div>


                    <a
                        class="room-action"
                        href="/reservation.php?room_id=1"
                    >
                        Reserve Room
                        <span>→</span>
                    </a>

                </div>

            </article>


            <!-- DELUXE ROOM -->

            <article class="room-card room-deluxe">

                <div class="room-image">

                    <span class="room-tag">
                        SIGNATURE
                    </span>

                </div>


                <div class="room-card-body">

                    <div class="room-card-top">

                        <h2>
                            Deluxe Room
                        </h2>

                        <span class="room-price">
                            $285
                            <small>/ night</small>
                        </span>

                    </div>


                    <p>
                        Elevated comfort with premium amenities
                        and beautiful views.
                    </p>


                    <div class="room-meta">

                        <span>
                            ♙ 3 guests
                        </span>

                        <span>
                            ◷ Flexible stay
                        </span>

                    </div>


                    <a
                        class="room-action"
                        href="/reservation.php?room_id=2"
                    >
                        Reserve Room
                        <span>→</span>
                    </a>

                </div>

            </article>


            <!-- EXECUTIVE SUITE -->

            <article class="room-card room-suite">

                <div class="room-image">

                    <span class="room-tag">
                        LUXURY
                    </span>

                </div>


                <div class="room-card-body">

                    <div class="room-card-top">

                        <h2>
                            Executive Suite
                        </h2>

                        <span class="room-price">
                            $450
                            <small>/ night</small>
                        </span>

                    </div>


                    <p>
                        Spacious luxury with refined facilities
                        and exceptional service.
                    </p>


                    <div class="room-meta">

                        <span>
                            ♙ 4 guests
                        </span>

                        <span>
                            ◷ Premium stay
                        </span>

                    </div>


                    <a
                        class="room-action"
                        href="/reservation.php?room_id=3"
                    >
                        Reserve Room
                        <span>→</span>
                    </a>

                </div>

            </article>


        </div>

    </section>

</main>


<!-- FOOTER -->

<footer class="site-footer">

    <div>

        <strong>
            Luxe<span>Stay</span>
        </strong>

        <p>
            Excellence in hospitality and refined comfort
            for the modern traveler.
        </p>

    </div>


    <div>

        <strong>
            Quick Links
        </strong>

        <p>
            <a href="/index.php">
                Home
            </a>
        </p>

        <p>
            <a href="/views/rooms/index.php">
                Rooms
            </a>
        </p>

        <p>
            <a href="/views/bookings/index.php">
                My Reservations
            </a>
        </p>

        <p>
            <a href="/views/payments/index.php">
                Payments
            </a>
        </p>

    </div>


    <div>

        <strong>
            Guest Care
        </strong>

        <p>
            <a href="/views/notifications/index.php">
                Notifications
            </a>
        </p>

        <p>
            <a href="/views/customers/profile.php">
                Profile
            </a>
        </p>

        <p>
            Secure booking & payment
        </p>

    </div>


    <div class="footer-bottom">

        © <?= date('Y') ?> LuxeStay.
        All rights reserved.

    </div>

</footer>


</body>

</html>