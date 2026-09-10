<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/OtpController.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$userId = (int)($_SESSION['user']['id'] ?? 0);

$reservationId = (int)(
    $_SESSION['otp_reservation_id'] ?? 0
);

if ($reservationId <= 0) {
    header('Location: /views/payments/index.php');
    exit;
}

$otpController = new OtpController();
$paymentController = new PaymentController();

$message = '';
$messageType = '';


/*
|--------------------------------------------------------------------------
| VERIFY OTP
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otpCode = trim(
        $_POST['otp_code'] ?? ''
    );

    if (!preg_match('/^[0-9]{6}$/', $otpCode)) {

        $message =
            'Please enter a valid 6-digit OTP.';

        $messageType = 'error';

    } else {

        $verified =
            $otpController->verifyPaymentOtp(
                $userId,
                $reservationId,
                $otpCode
            );

        if (!$verified) {

            $message =
                'Invalid or expired verification code.';

            $messageType = 'error';

        } else {

            /*
             * OTP VERIFIED
             *
             * For the current project demo, we now
             * complete the payment after successful
             * OTP verification.
             */

            $result =
                $paymentController->processPayment(
                    $reservationId,
                    $userId
                );

            if ($result['success']) {

                unset(
                    $_SESSION['otp_reservation_id']
                );

                header(
                    'Location: receipt.php?payment_id=' .
                    (int)$result['id']
                );

                exit;

            } else {

                $message =
                    $result['message']
                    ?? 'Payment could not be completed.';

                $messageType = 'error';
            }
        }
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
    Verify Payment | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

.otp-container {
    max-width: 600px;
    margin: 70px auto;
    padding: 35px;
}

.otp-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 35px;
    box-shadow:
        0 10px 30px
        rgba(15, 23, 42, 0.08);
    text-align: center;
}

.otp-input {
    width: 100%;
    box-sizing: border-box;
    padding: 16px;
    margin: 20px 0;
    border: 2px solid #d1d5db;
    border-radius: 10px;
    font-size: 28px;
    text-align: center;
    letter-spacing: 10px;
    font-weight: bold;
}

.otp-input:focus {
    outline: none;
    border-color: #14213d;
}

.verify-button {
    width: 100%;
    padding: 14px;
    border: 0;
    border-radius: 9px;
    background: #14213d;
    color: #ffffff;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
}

.verify-button:hover {
    background: #0f172a;
}

.otp-message {
    margin: 20px 0;
    padding: 13px;
    border-radius: 8px;
    font-weight: 600;
}

.otp-message.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #14213d;
    font-weight: 700;
    text-decoration: none;
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


<main class="otp-container">

<section class="otp-card">

    <p class="eyebrow">
        PAYMENT SECURITY
    </p>

    <h1>
        Verify Your Payment
    </h1>

    <p>
        A 6-digit verification code has been sent
        to your registered email address.
    </p>

    <p>
        Enter the code below to continue.
    </p>


    <?php if ($message !== ''): ?>

        <div
            class="otp-message <?= htmlspecialchars($messageType) ?>"
        >

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <form method="POST">

        <input
            type="text"
            name="otp_code"
            class="otp-input"
            placeholder="000000"
            maxlength="6"
            minlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            pattern="[0-9]{6}"
            required
        >

        <button
            type="submit"
            class="verify-button"
        >
            Verify OTP & Complete Payment
        </button>

    </form>


    <a
        href="/views/payments/index.php"
        class="back-link"
    >
        ← Back to Payments
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