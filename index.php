<?php

require_once __DIR__ . '/config/config.php';

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
    <?= htmlspecialchars(APP_NAME) ?>
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

    <?php if (isLoggedIn()): ?>

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

        <?php if (
            isset($_SESSION['user']['role'])
            && $_SESSION['user']['role'] === 'admin'
        ): ?>

            <a href="/views/admin/index.php">
                Admin Dashboard
            </a>

        <?php endif; ?>

        <a href="/logout.php">
            Logout
        </a>

    <?php else: ?>

        <a href="/login.php">
            Login
        </a>

        <a
            href="/register.php"
            class="register-button"
        >
            Register
        </a>

    <?php endif; ?>

</nav>


</header>


<main>


<section class="hero">

    <div>

        <p class="eyebrow">
            LUXURY HOTEL & RESORT
        </p>

        <h1>
            Find Your Perfect Stay
        </h1>

        <p>
            Experience unparalleled luxury and
            breathtaking views at LuxeStay.
        </p>

        <div class="hero-buttons">

            <a href="/views/rooms/index.php">
                Explore Rooms →
            </a>

            <?php if (isLoggedIn()): ?>

                <a
                    href="/views/bookings/index.php"
                    class="secondary"
                >
                    My Reservations
                </a>

            <?php else: ?>

                <a
                    href="/register.php"
                    class="secondary"
                >
                    Create Account
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>

<section class="welcome">

    <p class="eyebrow">
        ACCOMMODATION
    </p>

    <h2>
        Featured Room Types
    </h2>

    <div class="cards">

        <article>

            <h3>
                Standard Room
            </h3>

            <p>
                Comfortable and elegant accommodation
                for your stay.
            </p>

            <a href="/views/rooms/index.php">
                Explore Room →
            </a>

        </article>

        <article>

            <h3>
                Deluxe Room
            </h3>

            <p>
                Elevated comfort with premium amenities
                and beautiful views.
            </p>

            <a href="/views/rooms/index.php">
                Explore Room →
            </a>

        </article>

        <article>

            <h3>
                Executive Suite
            </h3>

            <p>
                Spacious luxury with refined facilities
                and exceptional service.
            </p>

            <a href="/views/rooms/index.php">
                Explore Suite →
            </a>

        </article>

    </div>

</section>


</main>

<footer>


<div>

    <strong>
        LuxeStay
    </strong>

    <p>
        Excellence in hospitality and refined
        comfort for the modern traveler.
    </p>

</div>

<div>

    <strong>
        Quick Links
    </strong>

    <p>
        <a href="/views/rooms/index.php">
            Rooms
        </a>
    </p>

    <?php if (isLoggedIn()): ?>

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

    <?php endif; ?>

    <p>
        Amenities
    </p>

    <p>
        Reviews
    </p>

</div>

<div>

    <strong>
        Contact Info
    </strong>

    <p>
        contact@luxestay.com
    </p>

    <p>
        +1 (555) 000-0000
    </p>

</div>


</footer>

</body>

</html>
