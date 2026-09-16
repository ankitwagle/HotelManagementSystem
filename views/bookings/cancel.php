<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';

requireLogin();

$userId =
    (int) ($_SESSION['user']['id'] ?? 0);

$reservationId =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

$message = (string) ($_GET['message'] ?? '');
$messageType = 'success';

/*
|--------------------------------------------------------------------------
| Cancel Reservation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservationId =
        (int) ($_POST['reservation_id'] ?? 0);

    if ($reservationId > 0) {

        $reservationController =
            new ReservationController();

        $result =
            $reservationController->requestCancellation(
                $reservationId,
                $userId
            );

        if ($result['success']) {

            header(
                'Location: /views/bookings/cancel.php?id=' .
                $reservationId . '&message=' . urlencode($result['message'])
            );

            exit;

        } else {

            $message =
                $result['message']
                ?? 'Unable to cancel reservation.';

            $messageType = 'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Reservation Details
|--------------------------------------------------------------------------
*/

$reservation = null;

if ($reservationId > 0) {

    $db = Database::connect();

    $stmt = $db->prepare("
        SELECT
            r.*,
            rm.room_type AS room_name,
            rm.price
        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE r.id = ?
          AND r.user_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $reservationId,
        $userId
    ]);

    $reservation =
        $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Invalid Reservation
|--------------------------------------------------------------------------
*/

if (!$reservation) {

    header(
        'Location: /views/bookings/index.php'
    );

    exit;
}

$paymentStmt = $db->prepare("
    SELECT id
    FROM payments
    WHERE booking_id = ?
      AND LOWER(TRIM(status)) = 'paid'
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    $reservationId
]);

$hasPaidPayment = (bool) $paymentStmt->fetchColumn();

$refundStmt = $db->prepare("
    SELECT amount, service_charge, refund_amount, refund_reference, refunded_at
    FROM payments
    WHERE booking_id = ?
      AND LOWER(TRIM(status)) = 'refunded'
    ORDER BY id DESC
    LIMIT 1
");
$refundStmt->execute([$reservationId]);
$refundDetails = $refundStmt->fetch(PDO::FETCH_ASSOC) ?: null;

if ($reservation['status'] !== 'cancel_requested'
    && $messageType === 'success') {
    $message = '';
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
    Reservation Details | <?= htmlspecialchars(APP_NAME) ?>
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

    .reservation-message {
        max-width: 900px;
        margin: 20px auto;
        padding: 15px;
        border-radius: 8px;
        font-weight: 600;
    }

    .reservation-message.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .reservation-message.success {
        background: #ecfdf3;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .cancel-button {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        background: #dc2626;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        font-size: 15px;
    }

    .cancel-button:hover {
        background: #b91c1c;
    }

    .status-panel {
        max-width: 900px;
        margin: 0 auto 28px;
        padding: 22px 26px;
        border-radius: 12px;
        border: 1px solid;
        text-align: left;
    }

    .status-panel h2 {
        margin: 0 0 6px;
    }

    .status-panel p {
        margin: 0;
    }

    .status-panel.pending {
        background: #fff8e6;
        border-color: #f1d48a;
        color: #765b12;
    }

    .status-panel.confirmed {
        background: #ecfdf3;
        border-color: #bbf7d0;
        color: #166534;
    }

    .status-panel.cancelled {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .status-panel.cancel-requested {
        background: #fff8e6;
        border-color: #f1d48a;
        color: #765b12;
    }

    .payment-button {
        display: inline-block;
        padding: 13px 22px;
        border: 0;
        border-radius: 8px;
        background: #b9954b;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        font-size: 15px;
    }

    .payment-button:hover {
        background: #987735;
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


<main>


<section class="welcome">

    <p class="eyebrow">
        LUXESTAY RESERVATION
    </p>

    <h1>
        Reservation #<?= (int) $reservation['id'] ?>
    </h1>

    <p>
        Here are the complete details of your reservation.
    </p>

</section>

<?php if ($reservation['status'] === 'pending'): ?>

    <div class="status-panel pending">

        <h2>Waiting for Admin Approval</h2>

        <p>
            Your reservation has been created successfully and is waiting
            for administrator approval. Payment will become available after
            your reservation is approved.
        </p>

    </div>

<?php elseif ($reservation['status'] === 'confirmed' && !$hasPaidPayment): ?>

    <div class="status-panel confirmed">

        <h2>Reservation Confirmed</h2>

        <p>
            Your stay is confirmed. Continue to the existing secure Stripe
            Checkout flow when you are ready.
        </p>

    </div>

<?php elseif ($reservation['status'] === 'confirmed' && $hasPaidPayment): ?>

    <div class="status-panel confirmed">
        <h2>Payment Completed</h2>
        <p>Your reservation is confirmed and payment has been completed.</p>
    </div>

<?php elseif ($reservation['status'] === 'cancelled'): ?>

    <div class="status-panel cancelled">

        <h2>Reservation Cancelled</h2>

        <p>
            This reservation is no longer active and cannot be paid.
        </p>

    </div>

<?php elseif ($reservation['status'] === 'cancel_requested'): ?>

    <div class="status-panel cancel-requested">
        <h2>Cancellation Requested</h2>
        <p>Waiting for Admin Approval</p>
    </div>

<?php endif; ?>


<?php if ($message !== ''): ?>

    <div
        class="reservation-message <?= htmlspecialchars($messageType) ?>"
    >

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<section class="welcome">

    <div class="cards">


        <article>

            <p class="eyebrow">
                ROOM
            </p>

            <h2>
                <?= htmlspecialchars(
                    $reservation['room_name'] ?? 'Room'
                ) ?>
            </h2>

            <p>

                <strong>
                    Price:
                </strong>

                $<?= htmlspecialchars(
                    number_format(
                        (float) $reservation['price'],
                        2
                    )
                ) ?>

                / night

            </p>

        </article>


        <article>

            <p class="eyebrow">
                STAY DETAILS
            </p>

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
                    Guests:
                </strong>

                <?= (int) $reservation['guests'] ?>

            </p>

        </article>


        <article>

            <p class="eyebrow">
                RESERVATION STATUS
            </p>

            <h2>
                <?= htmlspecialchars(
                    ucfirst($reservation['status'])
                ) ?>
            </h2>

            <p>

                <strong>
                    Booked On:
                </strong>

                <?= htmlspecialchars(
                    $reservation['created_at']
                ) ?>

            </p>

        </article>


        <?php if (!empty($reservation['special_requests'])): ?>

            <article>

                <p class="eyebrow">
                    SPECIAL REQUESTS
                </p>

                <p>

                    <?= htmlspecialchars(
                        $reservation['special_requests']
                    ) ?>

                </p>

            </article>

        <?php endif; ?>


    </div>


    <div class="hero-buttons">

        <a href="/views/bookings/index.php">
            ← Back to My Reservations
        </a>


        <?php if ($reservation['status'] === 'pending'): ?>

            <form
                method="POST"
                style="display: inline;"
                onsubmit="
                    return confirm(
                        'Are you sure you want to cancel this reservation?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="reservation_id"
                    value="<?= (int) $reservation['id'] ?>"
                >

                <button
                    type="submit"
                    class="cancel-button"
                >
                    Request Cancellation
                </button>

            </form>

        <?php elseif ($reservation['status'] === 'confirmed'): ?>

            <?php if (!$hasPaidPayment): ?>

                <form
                    method="POST"
                    action="/views/payments/index.php"
                    style="display: inline;"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="start_payment"
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

            <?php else: ?>

                <p>
                    Payment has already been completed for this reservation.
                </p>

            <?php endif; ?>

            <form
                method="POST"
                style="display: inline;"
                onsubmit="
                    return confirm(
                        'Are you sure you want to cancel this reservation?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="reservation_id"
                    value="<?= (int) $reservation['id'] ?>"
                >

                <button
                    type="submit"
                    class="cancel-button"
                >
                    Request Cancellation
                </button>

            </form>

        <?php elseif ($reservation['status'] === 'cancelled'): ?>

            <p>
                This reservation has already been cancelled.
            </p>

            <?php if ($refundDetails): ?>
                <article>
                    <p class="eyebrow">REFUND DETAILS</p>
                    <p><strong>Original Payment:</strong> $<?= number_format((float) $refundDetails['amount'], 2) ?></p>
                    <p><strong>Service Charge (10%):</strong> $<?= number_format((float) $refundDetails['service_charge'], 2) ?></p>
                    <p><strong>Refund Amount:</strong> $<?= number_format((float) $refundDetails['refund_amount'], 2) ?></p>
                    <p><strong>Refund Reference:</strong> <?= htmlspecialchars($refundDetails['refund_reference']) ?></p>
                    <p><strong>Refund Date:</strong> <?= htmlspecialchars($refundDetails['refunded_at']) ?></p>
                </article>
            <?php endif; ?>

        <?php elseif ($reservation['status'] === 'cancel_requested'): ?>

            <p>Cancellation Request Submitted. Waiting for Admin Approval.</p>

        <?php endif; ?>


    </div>

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