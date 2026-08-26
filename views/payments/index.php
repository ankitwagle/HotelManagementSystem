<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$userId = (int) ($_SESSION['user']['id'] ?? 0);

$controller = new PaymentController();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'make_payment') {

        $reservationId =
            (int) ($_POST['reservation_id'] ?? 0);

        $result = $controller->processPayment(
            $reservationId,
            $userId
        );

        if ($result['success']) {

            header(
                'Location: receipt.php?payment_id=' .
                (int) $result['id']
            );

            exit;
        }

        $message = $result['message'] ?? 'Payment failed.';
        $messageType = 'error';
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
        View and complete payment for your confirmed reservations.
    </p>

</section>


<?php if ($message !== ''): ?>

    <div class="payment-message <?= htmlspecialchars($messageType) ?>">

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

                $checkIn = new DateTime(
                    $reservation['check_in']
                );

                $checkOut = new DateTime(
                    $reservation['check_out']
                );

                $nights =
                    $checkIn->diff(
                        $checkOut
                    )->days;

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
                            $price,
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

                        /*
                         * Check whether this reservation
                         * already has a payment.
                         */
                        $payment = $controller->getPaymentByReservation(
                            (int) $reservation['id']
                        );

                        $isPaid =
                            $payment &&
                            strtolower(
                                (string) ($payment['status'] ?? '')
                            ) === 'paid';

                        ?>


                        <?php if ($isPaid): ?>

                            <div class="paid-message">

                                ✓ Payment Completed

                                <?php if (
                                    !empty(
                                        $payment['transaction_reference']
                                    )
                                ): ?>

                                    <br>

                                    Transaction:
                                    <?= htmlspecialchars(
                                        $payment['transaction_reference']
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <form
                                method="POST"
                                action=""
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="make_payment"
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
