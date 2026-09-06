<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Admin Access
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin access required.');
}

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$db = Database::connect();

/*
|--------------------------------------------------------------------------
| Payment Summary
|--------------------------------------------------------------------------
*/

$totalPaid = 0.00;

try {

    $totalPaid = (float) $db
        ->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE status = 'paid'
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $totalPaid = 0.00;
}


$todayPaid = 0.00;

try {

    $todayPaid = (float) $db
        ->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE status = 'paid'
              AND DATE(paid_at) = CURDATE()
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $todayPaid = 0.00;
}


$paidTransactions = 0;

try {

    $paidTransactions = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
            WHERE status = 'paid'
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $paidTransactions = 0;
}


/*
|--------------------------------------------------------------------------
| Pending Payments
|--------------------------------------------------------------------------
|
| A confirmed reservation without a successful payment.
|
*/

$pendingPayments = 0;

try {

    $pendingPayments = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM reservations r
            WHERE r.status = 'confirmed'
              AND NOT EXISTS (
                  SELECT 1
                  FROM payments p
                  WHERE p.booking_id = r.id
                    AND p.status = 'paid'
              )
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $pendingPayments = 0;
}


/*
|--------------------------------------------------------------------------
| Payment Records
|--------------------------------------------------------------------------
*/

$payments = [];

try {

    $stmt = $db->query("
        SELECT
            p.id,
            p.booking_id,
            p.amount,
            p.payment_method,
            p.transaction_reference,
            p.status,
            p.paid_at,
            p.created_at,

            u.name AS customer_name,
            u.email AS customer_email,

            rm.room_type AS room_name,

            r.check_in,
            r.check_out,
            r.guests,
            r.status AS reservation_status

        FROM payments p

        INNER JOIN reservations r
            ON p.booking_id = r.id

        INNER JOIN users u
            ON r.user_id = u.id

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        ORDER BY p.created_at DESC
    ");

    $payments = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $payments = [];
}


/*
|--------------------------------------------------------------------------
| Pending Payment Records
|--------------------------------------------------------------------------
*/

$pendingPaymentRecords = [];

try {

    $stmt = $db->query("
        SELECT
            r.id AS reservation_id,
            r.check_in,
            r.check_out,
            r.guests,
            r.created_at,

            u.name AS customer_name,
            u.email AS customer_email,

            rm.room_type AS room_name,
            rm.price

        FROM reservations r

        INNER JOIN users u
            ON r.user_id = u.id

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE r.status = 'confirmed'

          AND NOT EXISTS (
              SELECT 1
              FROM payments p
              WHERE p.booking_id = r.id
                AND p.status = 'paid'
          )

        ORDER BY r.created_at DESC
    ");

    $pendingPaymentRecords =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $pendingPaymentRecords = [];
}


/*
|--------------------------------------------------------------------------
| Monthly Revenue Chart Data
|--------------------------------------------------------------------------
|
| Only successful payments are included.
|
*/

$chartLabels = [];
$chartValues = [];

try {

    $stmt = $db->query("
        SELECT
            DATE_FORMAT(paid_at, '%Y-%m') AS payment_month,
            COALESCE(SUM(amount), 0) AS monthly_revenue
        FROM payments
        WHERE status = 'paid'
          AND paid_at IS NOT NULL
        GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
        ORDER BY payment_month ASC
    ");

    $monthlyRevenue =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

    foreach ($monthlyRevenue as $row) {

        $date = DateTime::createFromFormat(
            'Y-m',
            $row['payment_month']
        );

        $chartLabels[] =
            $date
            ? $date->format('M Y')
            : $row['payment_month'];

        $chartValues[] =
            (float) $row['monthly_revenue'];
    }

} catch (PDOException $e) {

    $chartLabels = [];
    $chartValues = [];
}


/*
|--------------------------------------------------------------------------
| Refund Requests
|--------------------------------------------------------------------------
*/

$refundRequests = 0;

try {

    $refundRequests = (int) $db
        ->query("
            SELECT COUNT(*)
            FROM payments
            WHERE status = 'refund_requested'
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $refundRequests = 0;
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
    Customer Payments | LuxeStay Admin
</title>

<link
    rel="stylesheet"
    href="../../public/css/style.css"
>

<!-- Highcharts -->
<script
    src="https://code.highcharts.com/highcharts.js"
></script>

<style>

    .payment-summary {
        margin-bottom: 30px;
    }

    .payment-summary article {
        border-top: 3px solid #8b6f3d;
    }

    .payment-summary h2 {
        color: #14213d;
    }

    .payment-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .payment-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
        background: #ffffff;
    }

    .payment-table th,
    .payment-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        vertical-align: middle;
    }

    .payment-table th {
        background: #f8fafc;
        color: #14213d;
        font-size: 13px;
        font-weight: 700;
    }

    .payment-table td {
        font-size: 13px;
    }

    .payment-table tr:hover td {
        background: #fafafa;
    }

    .status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-refund_requested {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-other {
        background: #e2e8f0;
        color: #334155;
    }

    .pending-section {
        margin-top: 35px;
    }

    .chart-section {
        margin-top: 35px;
    }

    .chart-button {
        display: inline-block;
        padding: 12px 20px;
        border: 0;
        border-radius: 8px;
        background: #14213d;
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
    }

    .chart-button:hover {
        background: #0f172a;
    }

    .chart-panel {
        display: none;
        margin-top: 20px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow:
            0 8px 25px rgba(15, 23, 42, 0.06);
    }

    .chart-panel.open {
        display: block;
    }

    #paymentRevenueChart {
        width: 100%;
        height: 420px;
    }

    .empty-state {
        padding: 30px;
        text-align: center;
        background: #ffffff;
        border-radius: 10px;
    }

    .transaction-reference {
        font-family: monospace;
        font-size: 12px;
    }

    .amount {
        font-weight: 700;
        color: #14213d;
    }

    .page-heading {
        margin-bottom: 25px;
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

<main>


<section class="welcome page-heading">

    <p class="eyebrow">
        LUXESTAY ADMINISTRATION
    </p>

    <h1>
        Customer Payments
    </h1>

    <p>
        View successful payments, pending payments,
        transactions, and revenue performance.
    </p>

</section>


<!-- PAYMENT SUMMARY -->

<section class="welcome payment-summary">

    <div class="cards">

        <article>

            <p class="eyebrow">
                TOTAL PAID
            </p>

            <h2>
                $<?= number_format(
                    $totalPaid,
                    2
                ) ?>
            </h2>

            <p>
                Total successful payment revenue.
            </p>

        </article>


        <article>

            <p class="eyebrow">
                TODAY'S PAID REVENUE
            </p>

            <h2>
                $<?= number_format(
                    $todayPaid,
                    2
                ) ?>
            </h2>

            <p>
                Successful payments received today.
            </p>

        </article>


        <article>

            <p class="eyebrow">
                PAID TRANSACTIONS
            </p>

            <h2>
                <?= $paidTransactions ?>
            </h2>

            <p>
                Completed payment transactions.
            </p>

        </article>


        <article>

            <p class="eyebrow">
                PENDING PAYMENTS
            </p>

            <h2>
                <?= $pendingPayments ?>
            </h2>

            <p>
                Confirmed reservations waiting for payment.
            </p>

        </article>


        <article>

            <p class="eyebrow">
                REFUND REQUESTS
            </p>

            <h2>
                <?= $refundRequests ?>
            </h2>

            <p>
                Payments requiring refund attention.
            </p>

        </article>

    </div>

</section>


<!-- SUCCESSFUL PAYMENTS -->

<section class="welcome">

    <p class="eyebrow">
        PAYMENT HISTORY
    </p>

    <h2>
        Successful Payments
    </h2>

    <?php if (empty($payments)): ?>

        <div class="empty-state">

            <h3>
                No payments found
            </h3>

            <p>
                Successful customer payments will appear here.
            </p>

        </div>

    <?php else: ?>

        <div class="payment-table-wrapper">

            <table class="payment-table">

                <thead>

                    <tr>

                        <th>
                            Payment ID
                        </th>

                        <th>
                            Reservation
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Room
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Method
                        </th>

                        <th>
                            Transaction Reference
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Payment Date
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($payments as $payment): ?>

                    <?php

                    $paymentStatus =
                        strtolower(
                            trim(
                                (string)
                                ($payment['status'] ?? '')
                            )
                        );

                    $statusClass =
                        in_array(
                            $paymentStatus,
                            [
                                'paid',
                                'pending',
                                'refund_requested'
                            ],
                            true
                        )
                        ? 'status-' . $paymentStatus
                        : 'status-other';

                    ?>

                    <tr>

                        <td>
                            #<?= (int) $payment['id'] ?>
                        </td>

                        <td>
                            #<?= (int) $payment['booking_id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $payment['customer_name']
                                ?? 'Unknown'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $payment['customer_email']
                                ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $payment['room_name']
                                ?? ''
                            ) ?>
                        </td>

                        <td class="amount">

                            $<?= number_format(
                                (float)
                                $payment['amount'],
                                2
                            ) ?>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $payment['payment_method']
                                ?? ''
                            ) ?>
                        </td>

                        <td class="transaction-reference">

                            <?= htmlspecialchars(
                                $payment['transaction_reference']
                                ?? ''
                            ) ?>

                        </td>

                        <td>

                            <span
                                class="status <?= htmlspecialchars($statusClass) ?>"
                            >
                                <?= htmlspecialchars(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $paymentStatus
                                        )
                                    )
                                ) ?>
                            </span>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $payment['paid_at']
                                ?? $payment['created_at']
                                ?? ''
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<!-- PENDING PAYMENTS -->

<section class="welcome pending-section">

    <p class="eyebrow">
        PAYMENT ATTENTION
    </p>

    <h2>
        Pending Payments
    </h2>

    <p>
        Confirmed reservations that do not yet have
        a successful payment.
    </p>

    <?php if (empty($pendingPaymentRecords)): ?>

        <div class="empty-state">

            <h3>
                No Pending Payments
            </h3>

            <p>
                All confirmed reservations have been paid.
            </p>

        </div>

    <?php else: ?>

        <div class="payment-table-wrapper">

            <table class="payment-table">

                <thead>

                    <tr>

                        <th>
                            Reservation
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Room
                        </th>

                        <th>
                            Check-in
                        </th>

                        <th>
                            Check-out
                        </th>

                        <th>
                            Guests
                        </th>

                        <th>
                            Amount Due
                        </th>

                        <th>
                            Reservation Date
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach (
                    $pendingPaymentRecords
                    as $pending
                ): ?>

                    <?php

                    $checkIn =
                        new DateTime(
                            $pending['check_in']
                        );

                    $checkOut =
                        new DateTime(
                            $pending['check_out']
                        );

                    $nights =
                        $checkIn->diff(
                            $checkOut
                        )->days;

                    if ($nights < 1) {
                        $nights = 1;
                    }

                    $amountDue =
                        $nights *
                        (float) $pending['price'];

                    ?>

                    <tr>

                        <td>
                            #<?= (int) $pending['reservation_id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['customer_name']
                                ?? 'Unknown'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['customer_email']
                                ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['room_name']
                                ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['check_in']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['check_out']
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $pending['guests'] ?>
                        </td>

                        <td class="amount">

                            $<?= number_format(
                                $amountDue,
                                2
                            ) ?>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $pending['created_at']
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<!-- CHART -->

<section class="welcome chart-section">

    <p class="eyebrow">
        REVENUE ANALYTICS
    </p>

    <h2>
        Payment Revenue Chart
    </h2>

    <p>
        The chart is hidden by default.
        Click the button below to view actual
        successful payment revenue by month.
    </p>

    <button
        type="button"
        id="checkChartButton"
        class="chart-button"
    >
        Check Chart
    </button>


    <div
        id="chartPanel"
        class="chart-panel"
    >

        <div id="paymentRevenueChart"></div>

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

<script>

    const chartButton =
        document.getElementById(
            'checkChartButton'
        );

    const chartPanel =
        document.getElementById(
            'chartPanel'
        );

    let chartLoaded = false;


    chartButton.addEventListener(
        'click',
        function () {

            const isOpen =
                chartPanel.classList.contains(
                    'open'
                );

            if (isOpen) {

                chartPanel.classList.remove(
                    'open'
                );

                chartButton.textContent =
                    'Check Chart';

                return;
            }


            chartPanel.classList.add(
                'open'
            );

            chartButton.textContent =
                'Hide Chart';


            if (chartLoaded) {
                return;
            }


            const chartLabels =
                <?= json_encode(
                    $chartLabels,
                    JSON_HEX_TAG |
                    JSON_HEX_APOS |
                    JSON_HEX_AMP |
                    JSON_HEX_QUOT
                ) ?>;

            const chartValues =
                <?= json_encode(
                    $chartValues
                ) ?>;


            Highcharts.chart(
                'paymentRevenueChart',
                {

                    chart: {
                        type: 'line'
                    },

                    title: {
                        text: 'Monthly Payment Revenue'
                    },

                    subtitle: {
                        text:
                            'Successful payments recorded by LuxeStay'
                    },

                    xAxis: {

                        categories:
                            chartLabels,

                        title: {
                            text: 'Month'
                        }

                    },

                    yAxis: {

                        min: 0,

                        title: {
                            text: 'Revenue (USD)'
                        }

                    },

                    tooltip: {

                        valuePrefix: '$',

                        valueDecimals: 2

                    },

                    series: [

                        {

                            name:
                                'Paid Revenue',

                            data:
                                chartValues

                        }

                    ],

                    credits: {
                        enabled: false
                    }

                }
            );

            chartLoaded = true;

        }
    );

</script>

</body>

</html>
