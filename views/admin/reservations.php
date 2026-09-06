<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $controller = new ReservationController();

    if ($reservationId <= 0) {

        $_SESSION['reservation_message'] = 'Invalid reservation.';

    } elseif ($action === 'approve') {

        $result = $controller->updateStatus(
            $reservationId,
            'confirmed'
        );

        $_SESSION['reservation_message'] = $result['message'];

    } elseif ($action === 'cancel') {

        $result = $controller->updateStatus(
            $reservationId,
            'cancelled'
        );

        $_SESSION['reservation_message'] = $result['message'];

    } else {

        $_SESSION['reservation_message'] = 'Invalid reservation action.';
    }

    header('Location: reservations.php');
    exit;
}

$controller = new ReservationController();
$reservations = $controller->allReservations();

$message = $_SESSION['reservation_message'] ?? '';
unset($_SESSION['reservation_message']);

?>

<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>All Reservations | LuxeStay Admin</title>

<link
    rel="stylesheet"
    href="../../public/css/style.css"
>

<style>

    .reservation-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px 20px 60px;
    }

    .reservation-header {
        margin-bottom: 28px;
    }

    .reservation-header .back-link {
        display: inline-block;
        margin-bottom: 22px;
    }

    .eyebrow {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 2px;
        color: #9a6b24;
        margin-bottom: 8px;
    }

    .reservation-header h1 {
        margin: 0 0 8px;
    }

    .reservation-header p {
        margin: 0;
    }

    .message {
        padding: 14px 18px;
        margin-bottom: 20px;
        border-radius: 8px;
        background: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        font-weight: 600;
    }

    .message-error {
        background: #f8d7da;
        color: #842029;
        border-color: #f1aeb5;
    }

    .reservation-card {
        background: #ffffff;
        border: 1px solid #e4e7ec;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
    }

    .reservation-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .reservation-card-header h2 {
        margin: 0;
        font-size: 17px;
    }

    .reservation-count {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 950px;
    }

    th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        text-align: left;
        padding: 13px 12px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    td {
        padding: 15px 12px;
        border-bottom: 1px solid #edf0f3;
        color: #1e293b;
        font-size: 13px;
        vertical-align: middle;
    }

    tbody tr:hover {
        background: #fafbfc;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    .reservation-id {
        font-weight: 700;
        color: #16213e;
    }

    .customer-name {
        font-weight: 600;
    }

    .price {
        font-weight: 700;
        white-space: nowrap;
    }

    .status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-confirmed {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #842029;
    }

    .status-completed {
        background: #cfe2ff;
        color: #084298;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .action-buttons form {
        margin: 0;
    }

    .btn {
        border: none;
        padding: 7px 11px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    .btn-approve {
        background: #198754;
        color: #ffffff;
    }

    .btn-approve:hover {
        background: #157347;
    }

    .btn-cancel {
        background: #dc3545;
        color: #ffffff;
    }

    .btn-cancel:hover {
        background: #bb2d3b;
    }

    .action-complete {
        color: #198754;
        font-weight: 700;
        font-size: 12px;
    }

    .action-cancelled {
        color: #842029;
        font-weight: 700;
        font-size: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 45px 20px;
        color: #64748b;
    }

    footer {
        margin-top: 40px;
    }

    @media (max-width: 700px) {

        .reservation-page {
            padding: 24px 12px 40px;
        }

        .reservation-card-header {
            align-items: flex-start;
            flex-direction: column;
        }

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

<main class="reservation-page">


<section class="reservation-header">

    <a
        class="back-link"
        href="index.php"
    >
        ← Back to Admin Dashboard
    </a>

    <div class="eyebrow">
        LUXESTAY ADMINISTRATION
    </div>

    <h1>
        All Reservations
    </h1>

    <p>
        View and manage all hotel reservations.
    </p>

</section>

<?php if ($message !== ''): ?>

    <div class="message">
        <?= htmlspecialchars($message) ?>
    </div>

<?php endif; ?>

<section class="reservation-card">

    <div class="reservation-card-header">

        <h2>
            Reservation History
        </h2>

        <span class="reservation-count">
            <?= count($reservations) ?>
            <?= count($reservations) === 1 ? 'reservation' : 'reservations' ?>
        </span>

    </div>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Guests</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($reservations)): ?>

                <tr>

                    <td
                        colspan="11"
                        class="empty-state"
                    >
                        No reservations found.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($reservations as $reservation): ?>

                    <?php

                    $status = strtolower(
                        trim($reservation['status'] ?? 'pending')
                    );

                    $customerName =
                        $reservation['customer_name']
                        ?? $reservation['name']
                        ?? 'Unknown';

                    $roomName =
                        $reservation['room_name']
                        ?? $reservation['room_type']
                        ?? (
                            'Room #' .
                            ($reservation['room_id'] ?? '')
                        );

                    $price =
                        $reservation['total_amount']
                        ?? $reservation['price']
                        ?? 0;

                    ?>

                    <tr>

                        <td class="reservation-id">
                            #<?= (int) $reservation['id'] ?>
                        </td>

                        <td class="customer-name">
                            <?= htmlspecialchars($customerName) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['email'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($roomName) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['check_in'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['check_out'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= (int) (
                                $reservation['guests'] ?? 0
                            ) ?>
                        </td>

                        <td class="price">
                            $<?= number_format(
                                (float) $price,
                                2
                            ) ?>
                        </td>

                        <td>

                            <span
                                class="status status-<?= htmlspecialchars($status) ?>"
                            >
                                <?= htmlspecialchars(
                                    ucfirst($status)
                                ) ?>
                            </span>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $reservation['created_at'] ?? ''
                            ) ?>
                        </td>

                        <td>

                            <?php if ($status === 'pending'): ?>

                                <div class="action-buttons">

                                    <form
                                        method="POST"
                                        action="reservations.php"
                                        onsubmit="return confirm('Approve this reservation?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="reservation_id"
                                            value="<?= (int) $reservation['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-approve"
                                        >
                                            Approve
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        action="reservations.php"
                                        onsubmit="return confirm('Cancel this reservation?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="reservation_id"
                                            value="<?= (int) $reservation['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cancel"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-cancel"
                                        >
                                            Cancel
                                        </button>

                                    </form>

                                </div>

                            <?php elseif ($status === 'confirmed'): ?>

                                <span class="action-complete">
                                    Confirmed
                                </span>

                            <?php elseif ($status === 'cancelled'): ?>

                                <span class="action-cancelled">
                                    Cancelled
                                </span>

                            <?php elseif ($status === 'completed'): ?>

                                <span class="action-complete">
                                    Completed
                                </span>

                            <?php else: ?>

                                <span>
                                    —
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


</main>

<footer>


<div>

    <strong>
        LuxeStay
    </strong>

    <p>
        Hotel Management Administration System.
    </p>

</div>


</footer>

</body>

</html>
