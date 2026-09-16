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

<link
    rel="stylesheet"
    href="/public/css/customer.css"
>

</head>

<body class="luxury-home">

<header class="site-header">

    <div class="logo">

        <a
            href="/index.php"
            aria-label="LuxeStay home"
        >
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

    </div>

    <nav>

        <a
            class="active"
            href="/index.php"
        >
            Home
        </a>

        <?php if (
            isLoggedIn() &&
            ($_SESSION['user']['role'] ?? '') === 'admin'
        ): ?>

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
                Notifications<?php require __DIR__ . '/views/partials/notification-badge.php'; ?>
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

        <?php else: ?>

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
                    Notifications<?php require __DIR__ . '/views/partials/notification-badge.php'; ?>
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

            <?php else: ?>

                <a
                    class="nav-cta"
                    href="/login.php"
                >
                    Login
                </a>

            <?php endif; ?>

        <?php endif; ?>

    </nav>

</header>


<main>

<!-- ================= HERO ================= -->

<section class="hero">

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <p class="eyebrow">
            LUXESTAY HOTEL & RESORT
        </p>

        <h1>
            Stay somewhere
            <br>
            <em>extraordinary.</em>
        </h1>

        <p class="hero-copy">
            Refined rooms, thoughtful service, and
            memorable stays — all in one elegant destination.
        </p>

        <div class="hero-buttons">

            <a href="/views/rooms/index.php">
                Explore Rooms
                <span>→</span>
            </a>

            <?php if (isLoggedIn()): ?>

                <?php if (
                    ($_SESSION['user']['role'] ?? '') === 'admin'
                ): ?>

                    <a
                        href="/views/admin/index.php"
                        class="secondary"
                    >
                        Admin Dashboard
                    </a>

                <?php else: ?>

                    <a
                        href="/views/bookings/index.php"
                        class="secondary"
                    >
                        My Reservations
                    </a>

                <?php endif; ?>

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


    <div class="hero-booking-note">

        <span class="hero-note-dot"></span>

        <div>

            <strong>
                Luxury made simple
            </strong>

            <small>
                Secure booking · Verified payments · Personal service
            </small>

        </div>

    </div>

</section>


<!-- ================= INTRO ================= -->

<section class="welcome intro-section">

    <div class="section-heading">

        <p class="eyebrow">
            A CURATED STAY
        </p>

        <h2>
            Rooms designed for
            <em>rest.</em>
        </h2>

        <p>
            Choose an accommodation that matches your
            trip, from effortless comfort to spacious luxury.
        </p>

    </div>


    <!-- ROOM CARDS -->

    <div class="cards room-preview-grid">


        <!-- STANDARD -->

        <article class="room-card room-standard">

            <div class="room-image">

                <span class="room-tag">
                    COMFORT
                </span>

            </div>

            <div class="room-card-body">

                <div class="room-card-top">

                    <h3>
                        Standard Room
                    </h3>

                    <span class="room-price">
                        $150
                        <small>/ night</small>
                    </span>

                </div>

                <p>
                    Warm, comfortable accommodation
                    with everything you need for a
                    relaxing stay.
                </p>

                <a href="/views/rooms/index.php">
                    View room
                    <span>→</span>
                </a>

            </div>

        </article>


        <!-- DELUXE -->

        <article class="room-card room-deluxe">

            <div class="room-image">

                <span class="room-tag">
                    SIGNATURE
                </span>

            </div>

            <div class="room-card-body">

                <div class="room-card-top">

                    <h3>
                        Deluxe Room
                    </h3>

                    <span class="room-price">
                        $285
                        <small>/ night</small>
                    </span>

                </div>

                <p>
                    Elevated comfort with premium
                    amenities, generous space, and
                    beautiful views.
                </p>

                <a href="/views/rooms/index.php">
                    View room
                    <span>→</span>
                </a>

            </div>

        </article>


        <!-- SUITE -->

        <article class="room-card room-suite">

            <div class="room-image">

                <span class="room-tag">
                    LUXURY
                </span>

            </div>

            <div class="room-card-body">

                <div class="room-card-top">

                    <h3>
                        Executive Suite
                    </h3>

                    <span class="room-price">
                        $450
                        <small>/ night</small>
                    </span>

                </div>

                <p>
                    Spacious luxury with refined
                    facilities and exceptional service
                    for special stays.
                </p>

                <a href="/views/rooms/index.php">
                    View suite
                    <span>→</span>
                </a>

            </div>

        </article>

    </div>

</section>


<!-- ================= EXPERIENCE ================= -->

<section class="experience-section">

    <div class="experience-copy">

        <p class="eyebrow">
            THE LUXESTAY EXPERIENCE
        </p>

        <h2>
            Every detail,
            <br>
            <em>beautifully considered.</em>
        </h2>

        <p>
            From the moment you arrive, LuxeStay is
            designed around comfort, convenience,
            and a calm sense of luxury.
        </p>

    </div>


    <div class="feature-grid">


        <div class="feature-item">

            <span>
                01
            </span>

            <strong>
                Elegant spaces
            </strong>

            <p>
                Thoughtfully presented rooms for
                restful stays.
            </p>

        </div>


        <div class="feature-item">

            <span>
                02
            </span>

            <strong>
                Easy reservations
            </strong>

            <p>
                Simple booking with clear
                availability and pricing.
            </p>

        </div>


        <div class="feature-item">

            <span>
                03
            </span>

            <strong>
                Secure payments
            </strong>

            <p>
                OTP verification followed by
                secure Stripe checkout.
            </p>

        </div>


        <div class="feature-item">

            <span>
                04
            </span>

            <strong>
                Personal service
            </strong>

            <p>
                Helpful support from booking
                to checkout.
            </p>

        </div>

    </div>

</section>


<!-- ================= AMENITIES ================= -->

<section class="amenities-section">

    <div class="section-heading">

        <p class="eyebrow">
            EVERYTHING YOU NEED
        </p>

        <h2>
            Comfort beyond
            <em>the room.</em>
        </h2>

        <p>
            Thoughtful amenities designed to make
            every part of your stay effortless.
        </p>

    </div>


    <div class="amenities-grid">


        <div class="amenity-card">

            <div class="amenity-icon">
                ✦
            </div>

            <h3>
                Premium Comfort
            </h3>

            <p>
                Relax in carefully designed spaces
                created for a peaceful stay.
            </p>

        </div>


        <div class="amenity-card">

            <div class="amenity-icon">
                ◇
            </div>

            <h3>
                Beautiful Views
            </h3>

            <p>
                Wake up to elegant surroundings
                and memorable views.
            </p>

        </div>


        <div class="amenity-card">

            <div class="amenity-icon">
                ◎
            </div>

            <h3>
                Guest Service
            </h3>

            <p>
                Friendly service from reservation
                through checkout.
            </p>

        </div>


        <div class="amenity-card">

            <div class="amenity-icon">
                ✓
            </div>

            <h3>
                Secure Booking
            </h3>

            <p>
                Verified reservations and secure
                payment processing for peace of mind.
            </p>

        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta-section">

    <div>

        <p class="eyebrow">
            YOUR NEXT STAY
        </p>

        <h2>
            Make room for something memorable.
        </h2>

        <a href="/views/rooms/index.php">
            Discover LuxeStay
            <span>→</span>
        </a>

    </div>

</section>

</main>


<!-- ================= FOOTER ================= -->

<footer class="site-footer">


    <!-- BRAND -->

    <div>

        <strong>
            Luxe<span>Stay</span>
        </strong>

        <p>
            Excellence in hospitality and refined
            comfort for the modern traveler.
        </p>

    </div>


    <!-- QUICK LINKS -->

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


        <?php if (isLoggedIn()): ?>

            <?php if (
                ($_SESSION['user']['role'] ?? '') !== 'admin'
            ): ?>

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

                <a href="/views/notifications/index.php">
                    Notifications
                </a>

            </p>


            <p>

                <a href="/views/customers/profile.php">
                    Profile
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


    <!-- ADMIN LINKS -->

    <?php if (
        isLoggedIn() &&
        ($_SESSION['user']['role'] ?? '') === 'admin'
    ): ?>

        <div>

            <strong>
                Administration
            </strong>

            <p>

                <a href="/views/admin/index.php">
                    Dashboard
                </a>

            </p>

            <p>

                <a href="/views/admin/reservations.php">
                    Reservations
                </a>

            </p>

            <p>

                <a href="/views/admin/customers.php">
                    Guests
                </a>

            </p>

            <p>

                <a href="/views/admin/rooms.php">
                    Rooms
                </a>

            </p>

            <p>

                <a href="/views/admin/payments.php">
                    Payments
                </a>

            </p>

            <p>

                <a href="/views/admin/reports.php">
                    Reports
                </a>

            </p>

        </div>

    <?php else: ?>


        <!-- CONTACT -->

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

    <?php endif; ?>


    <div class="footer-bottom">

        © <?= date('Y') ?> LuxeStay.
        All rights reserved.

    </div>

</footer>


</body>
</html>