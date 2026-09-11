<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';
require_once __DIR__ . '/../../controllers/OtpController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$userId = (int) ($_SESSION['user']['id'] ?? 0);

$paymentController = new PaymentController();
$otpController = new OtpController();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {

        $reservationId =
            (int) ($_POST['reservation_id'] ?? 0);

        /*
         * First verify that the reservation
         * belongs to this logged-in user.
         */
        $reservationResult =
            $paymentController->getReservationForPayment(
                $reservationId,
                $userId
            );

        if (!$reservationResult['success']) {

            $message =
                $reservationResult['message'];

            $messageType = 'error';

        } else {

            $reservation =
                $reservationResult['reservation'];

            /*
             * Get user email and name.
             */
            $email =
                $_SESSION['user']['email']
                ?? $reservation['customer_email']
                ?? '';

            $name =
                $_SESSION['user']['name']
                ?? $reservation['customer_name']
                ?? 'Guest';

            if (empty($email)) {

                $message =
                    'Your account does not have an email address.';

                $messageType = 'error';

            } else {

                $otpResult =
                    $otpController->sendPaymentOtp(
                        $userId,
                        $reservationId,
                        $email,
                        $name
                    );

                if ($otpResult['success']) {

                    /*
                     * Store reservation temporarily
                     * for OTP verification.
                     */
                    $_SESSION['otp_reservation_id'] =
                        $reservationId;

                    header(
                        'Location: verify-otp.php'
                    );

                    exit;

                } else {

                    $message =
                        $otpResult['message'];

                    $messageType = 'error';
                }
            }
        }
    }
}


/*
 * Load all reservations belonging to the
 * currently logged-in customer.
 */
require_once __DIR__ . '/../../config/database.php';

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

$stmt->execute([
    $userId
]);

$reservations =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Load all successful payments once.
 *
 * This prevents a paid reservation from showing the
 * "Proceed to Payment" button again after refresh.
 */
$paidPaymentsByReservation = [];

try {
    $paidStmt = $db->query("
        SELECT
            booking_id,
            amount,
            transaction_reference,
            paid_at
        FROM payments
        WHERE LOWER(TRIM(status)) = 'paid'
        ORDER BY id DESC
    ");

    while ($paidPayment = $paidStmt->fetch(PDO::FETCH_ASSOC)) {
        $bookingId = (int) $paidPayment['booking_id'];

        /*
         * Keep the latest paid record for each reservation.
         */
        if (!isset($paidPaymentsByReservation[$bookingId])) {
            $paidPaymentsByReservation[$bookingId] = $paidPayment;
        }
    }
} catch (PDOException $e) {
    $paidPaymentsByReservation = [];
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
    Payments | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .payment-message {
        max-width: 900px;
        margin: 0 auto 25px;
        padding: 15px 18px;
        border-radius: 10px;
        font-weight: 600;
    }

    .payment-message.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .payment-message.success {
        background: #ecfdf3;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .payment-card {
        position: relative;
    }

    .payment-status {
        display: inline-block;
        padding: 6px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .payment-status.confirmed {
        background: #dcfce7;
        color: #166534;
    }

    .payment-status.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .payment-status.cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    .payment-button {
        display: inline-block;
        margin-top: 12px;
        padding: 11px 17px;
        border: 0;
        border-radius: 8px;
        background: #14213d;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }

    .payment-button:hover {
        background: #0f172a;
    }

    .paid-message {
        margin-top: 15px;
        padding: 12px 15px;
        background: #ecfdf3;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        color: #166534;
        font-weight: 700;
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
        LUXESTAY PAYMENTS
    </p>

    <h1>
        Payments
    </h1>

    <p>
        Verify your payment securely using an OTP sent to your registered email.
    </p>

</section>


<?php if ($message !== ''): ?>

    <div
        class="payment-message <?= htmlspecialchars($messageType) ?>"
    >

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


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

                $checkIn =
                    new DateTime($reservation['check_in']);

                $checkOut =
                    new DateTime($reservation['check_out']);

                $nights =
                    $checkIn->diff($checkOut)->days;

                if ($nights < 1) {
                    $nights = 1;
                }

                $price =
                    (float) $reservation['price'];

                $total =
                    $nights * $price;

                $status =
                    strtolower(
                        trim(
                            (string) $reservation['status']
                        )
                    );

                ?>

                <article class="payment-card">

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

                        <strong>Check-in:</strong>

                        <?= htmlspecialchars(
                            $reservation['check_in']
                        ) ?>

                    </p>

                    <p>

                        <strong>Check-out:</strong>

                        <?= htmlspecialchars(
                            $reservation['check_out']
                        ) ?>

                    </p>

                    <p>

                        <strong>Number of nights:</strong>

                        <?= $nights ?>

                    </p>

                    <p>

                        <strong>Guests:</strong>

                        <?= (int) $reservation['guests'] ?>

                    </p>

                    <p>

                        <strong>Price per night:</strong>

                        $<?= number_format($price, 2) ?>

                    </p>

                    <p>

                        <strong>Total Amount:</strong>

                        $<?= number_format($total, 2) ?>

                    </p>

                    <p>

                        <strong>Reservation Status:</strong>

                        <span
                            class="payment-status <?= htmlspecialchars($status) ?>"
                        >
                            <?= htmlspecialchars(
                                ucfirst($status)
                            ) ?>
                        </span>

                    </p>


                    <?php if ($status === 'confirmed'): ?>

                        <?php

                        $payment =
                            $paidPaymentsByReservation[
                                (int) $reservation['id']
                            ] ?? null;

                        $isPaid =
                            $payment !== null;

                        ?>

                        <?php if ($isPaid): ?>

                            <div class="paid-message">

                                ✓ Payment Completed

                                <?php if (
                                    !empty(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    Transaction:

                                    <?= htmlspecialchars(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="send_otp"
                                >

                                <input
                                    type="hidden"
                                    name="reservation_id"
                                    value="<?= (int) $reservation['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="payment-button"
                                >
                                    Proceed to Payment →
                                </button>

                            </form>

                        <?php endif; ?>


                    <?php elseif ($status === 'pending'): ?>

                        <p>
                            Payment will be available after your
                            reservation is approved by the administrator.
                        </p>


                    <?php elseif ($status === 'cancelled'): ?>

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