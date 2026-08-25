
<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$userId = (int) ($_SESSION['user']['id'] ?? 0);

$paymentId = (int) ($_GET['payment_id'] ?? 0);

if ($userId <= 0 || $paymentId <= 0) {
    header('Location: /views/payments/index.php');
    exit;
}

$db = Database::connect();

/*
 * Get the payment together with the reservation,
 * room and customer information.
 *
 * payments.booking_id contains the reservation ID
 * in the current database structure.
 */
$stmt = $db->prepare("
    SELECT
        p.id AS payment_id,
        p.booking_id,
        p.amount,
        p.payment_method,
        p.transaction_reference,
        p.status AS payment_status,
        p.paid_at,
        p.created_at AS payment_created_at,

        r.id AS reservation_id,
        r.check_in,
        r.check_out,
        r.guests,
        r.status AS reservation_status,

        rm.room_type AS room_name,
        rm.price AS room_price,

        u.name AS customer_name,
        u.email AS customer_email

    FROM payments p

    INNER JOIN reservations r
        ON p.booking_id = r.id

    INNER JOIN rooms rm
        ON r.room_id = rm.id

    INNER JOIN users u
        ON r.user_id = u.id

    WHERE p.id = ?
      AND r.user_id = ?

    LIMIT 1
");

$stmt->execute([
    $paymentId,
    $userId
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: /views/payments/index.php');
    exit;
}

/*
 * Calculate nights.
 */
$checkIn = new DateTime($payment['check_in']);
$checkOut = new DateTime($payment['check_out']);

$nights = $checkIn->diff($checkOut)->days;

if ($nights < 1) {
    $nights = 1;
}

$amount = (float) $payment['amount'];

$paymentStatus = strtolower(
    trim((string) $payment['payment_status'])
);

$paidAt = $payment['paid_at'] ?: $payment['payment_created_at'];

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
        Payment Receipt | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .receipt-wrapper {
            max-width: 850px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .receipt-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 40px;
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .receipt-success {
            text-align: center;
            margin-bottom: 35px;
        }

        .receipt-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dcfce7;
            color: #166534;
            font-size: 30px;
            font-weight: 700;
        }

        .receipt-success h1 {
            margin-bottom: 8px;
        }

        .receipt-success p {
            color: #64748b;
        }

        .receipt-status {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 14px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .receipt-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .receipt-section h2 {
            margin-bottom: 18px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            gap: 25px;
            padding: 11px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .receipt-row:last-child {
            border-bottom: 0;
        }

        .receipt-label {
            color: #64748b;
        }

        .receipt-value {
            font-weight: 600;
            text-align: right;
        }

        .receipt-total {
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .receipt-total strong:last-child {
            font-size: 25px;
            color: #14213d;
        }

        .receipt-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .receipt-button {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            background: #14213d;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
        }

        .receipt-button:hover {
            background: #0f172a;
        }

        .receipt-button.secondary {
            background: #ffffff;
            color: #14213d;
            border: 1px solid #cbd5e1;
        }

        .receipt-button.secondary:hover {
            background: #f8fafc;
        }

        @media print {

            header,
            footer,
            .receipt-actions {
                display: none;
            }

            body {
                background: #ffffff;
            }

            .receipt-wrapper {
                margin: 0;
                max-width: 100%;
            }

            .receipt-card {
                border: 0;
                box-shadow: none;
            }
        }

        @media (max-width: 600px) {

            .receipt-card {
                padding: 25px 20px;
            }

            .receipt-row {
                flex-direction: column;
                gap: 4px;
            }

            .receipt-value {
                text-align: left;
            }

            .receipt-total {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

        }

    </style>

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

    <div class="receipt-wrapper">

        <div class="receipt-card">

            <div class="receipt-success">

                <div class="receipt-icon">
                    ✓
                </div>

                <p class="eyebrow">
                    LUXESTAY PAYMENT
                </p>

                <h1>
                    Payment Successful
                </h1>

                <p>
                    Your payment has been completed successfully.
                </p>

                <span class="receipt-status">
                    <?= htmlspecialchars($paymentStatus) ?>
                </span>

            </div>


            <section class="receipt-section">

                <h2>
                    Payment Details
                </h2>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Payment ID
                    </span>

                    <span class="receipt-value">
                        #<?= (int) $payment['payment_id'] ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Reservation
                    </span>

                    <span class="receipt-value">
                        #<?= (int) $payment['reservation_id'] ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Transaction Reference
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['transaction_reference']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Payment Method
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['payment_method']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Payment Date
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $paidAt
                        ) ?>
                    </span>

                </div>

            </section>


            <section class="receipt-section">

                <h2>
                    Guest Information
                </h2>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Guest Name
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['customer_name']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Email
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['customer_email']
                        ) ?>
                    </span>

                </div>

            </section>


            <section class="receipt-section">

                <h2>
                    Reservation Details
                </h2>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Room
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['room_name']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Check-in
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['check_in']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Check-out
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            $payment['check_out']
                        ) ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Number of Nights
                    </span>

                    <span class="receipt-value">
                        <?= $nights ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Guests
                    </span>

                    <span class="receipt-value">
                        <?= (int) $payment['guests'] ?>
                    </span>

                </div>

                <div class="receipt-row">

                    <span class="receipt-label">
                        Reservation Status
                    </span>

                    <span class="receipt-value">
                        <?= htmlspecialchars(
                            ucfirst(
                                $payment['reservation_status']
                            )
                        ) ?>
                    </span>

                </div>

            </section>


            <div class="receipt-total">

                <strong>
                    Total Paid
                </strong>

                <strong>
                    $<?= number_format(
                        $amount,
                        2
                    ) ?>
                </strong>

            </div>


            <div class="receipt-actions">

                <a
                    href="/views/payments/index.php"
                    class="receipt-button secondary"
                >
                    Back to Payments
                </a>

                <a
                    href="/views/bookings/index.php"
                    class="receipt-button secondary"
                >
                    My Reservations
                </a>

                <button
                    type="button"
                    class="receipt-button"
                    onclick="window.print()"
                >
                    Print Receipt
                </button>

            </div>

        </div>

    </div>

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

