<?php

require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$sessionId = trim($_GET['session_id'] ?? '');

if ($sessionId === '') {
    header('Location: /views/payments/index.php');
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
    Payment Processing | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<link
    rel="stylesheet"
    href="/public/css/customer.css"
>
<style>

.success-container {
    max-width: 650px;
    margin: 70px auto;
    padding: 35px;
}

.success-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    box-shadow:
        0 10px 30px
        rgba(15, 23, 42, 0.08);
}

.success-icon {
    font-size: 60px;
    margin-bottom: 15px;
}

.success-card h1 {
    margin-bottom: 15px;
}

.success-card p {
    color: #6b7280;
    line-height: 1.6;
}

.payment-note {
    margin-top: 25px;
    padding: 15px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}

.payment-button {
    display: inline-block;
    margin-top: 25px;
    padding: 13px 22px;
    border-radius: 9px;
    background: #14213d;
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
}

.payment-button:hover {
    background: #0f172a;
}

</style>

</head>

<body class="customer-ui">

<header>

    <div class="logo">

        <a
            href="/index.php"
            style="
                color: inherit;
                text-decoration: none;
            "
        >
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
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
            Notifications<?php require __DIR__ . '/../partials/notification-badge.php'; ?>
        </a>

        <a href="/views/customers/profile.php">
            Profile
        </a>

        <a href="/logout.php">
            Logout
        </a>

    </nav>

</header>


<main class="success-container">

<section class="success-card">

    <div class="success-icon">
        💳
    </div>

    <p class="eyebrow">
        STRIPE PAYMENT
    </p>

    <h1>
        Payment Processing
    </h1>

    <p>
        Thank you. Stripe has returned you to LuxeStay.
    </p>

    <div class="payment-note">

        <strong>
            Your payment is being confirmed.
        </strong>

        <p>
            Please wait a moment while LuxeStay
            receives the payment confirmation from Stripe.
        </p>

    </div>

    <a
        href="/views/payments/index.php"
        class="payment-button"
    >
        View My Payments
    </a>

</section>

</main>


<footer>

<div>

    <strong>
        LuxeStay
    </strong>

    <p>
        Secure hotel reservation payments.
    </p>

</div>

</footer>

</body>

</html>