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
| Helper Functions
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($amount): string
{
    return '$' . number_format(
        (float) $amount,
        2
    );
}

function validDate(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate(
        (int) $parts[1],
        (int) $parts[2],
        (int) $parts[0]
    );
}

/*
|--------------------------------------------------------------------------
| Read Filters
|--------------------------------------------------------------------------
*/

$period = trim($_GET['period'] ?? 'overall');
$roomType = trim($_GET['room_type'] ?? '');
$status = strtolower(trim($_GET['status'] ?? ''));

$fromDateInput = trim($_GET['from_date'] ?? '');
$toDateInput = trim($_GET['to_date'] ?? '');

$allowedPeriods = [
    'overall',
    'today',
    'month',
    'year',
    'custom'
];

$allowedStatuses = [
    '',
    'pending',
    'confirmed',
    'approved',
    'cancelled'
];

if (!in_array($period, $allowedPeriods, true)) {
    $period = 'overall';
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

/*
|--------------------------------------------------------------------------
| Calculate Actual Date Range
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$fromDate = '';
$toDate = '';

if ($period === 'today') {

    $fromDate = $today;
    $toDate = $today;

} elseif ($period === 'month') {

    $fromDate = date('Y-m-01');
    $toDate = $today;

} elseif ($period === 'year') {

    $fromDate = date('Y-01-01');
    $toDate = $today;

} elseif ($period === 'custom') {

    if (
        validDate($fromDateInput) &&
        validDate($toDateInput) &&
        $fromDateInput <= $toDateInput
    ) {
        $fromDate = $fromDateInput;
        $toDate = $toDateInput;
    }

}

/*
|--------------------------------------------------------------------------
| Room Types
|--------------------------------------------------------------------------
*/

$roomTypes = [];

try {

    $roomTypes = $db
        ->query("
            SELECT DISTINCT room_type
            FROM rooms
            WHERE room_type IS NOT NULL
              AND room_type <> ''
            ORDER BY room_type ASC
        ")
        ->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {

    $roomTypes = [];

}

/*
|--------------------------------------------------------------------------
| Reservation Filter
|--------------------------------------------------------------------------
|
| These filters are used for:
|
| - Total bookings
| - Customer booking frequency
| - Most booked room types
| - Most booked physical rooms
|
| Default = confirmed + approved bookings.
|
*/

$reservationWhere = [];
$reservationParams = [];

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $reservationWhere[] =
        "LOWER(r.status) = ?";

    $reservationParams[] =
        strtolower($status);

} else {

    $reservationWhere[] =
        "LOWER(r.status) IN ('confirmed', 'approved')";

}

/*
|--------------------------------------------------------------------------
| Room Type
|--------------------------------------------------------------------------
*/

if ($roomType !== '') {

    $reservationWhere[] =
        "rm.room_type = ?";

    $reservationParams[] =
        $roomType;
}

/*
|--------------------------------------------------------------------------
| Booking Date
|--------------------------------------------------------------------------
|
| Booking reports use reservation created_at.
|
*/

if ($fromDate !== '' && $toDate !== '') {

    $reservationWhere[] =
        "DATE(r.created_at) >= ?";

    $reservationParams[] =
        $fromDate;

    $reservationWhere[] =
        "DATE(r.created_at) <= ?";

    $reservationParams[] =
        $toDate;
}

$reservationWhereSql =
    implode(' AND ', $reservationWhere);

/*
|--------------------------------------------------------------------------
| Selected Period - Total Bookings
|--------------------------------------------------------------------------
*/

$selectedBookings = 0;

try {

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM reservations r
        INNER JOIN rooms rm
            ON r.room_id = rm.id
        WHERE {$reservationWhereSql}
    ");

    $stmt->execute($reservationParams);

    $selectedBookings =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $selectedBookings = 0;

}

/*
|--------------------------------------------------------------------------
| Revenue Filters
|--------------------------------------------------------------------------
|
| Revenue comes from successfully paid payments.
|
*/

$revenueWhere = [
    "LOWER(p.status) = 'paid'",
    "p.paid_at IS NOT NULL"
];

$revenueParams = [];

/*
|--------------------------------------------------------------------------
| Revenue Room Type
|--------------------------------------------------------------------------
*/

if ($roomType !== '') {

    $revenueWhere[] =
        "rm.room_type = ?";

    $revenueParams[] =
        $roomType;
}

/*
|--------------------------------------------------------------------------
| Revenue Reservation Status
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $revenueWhere[] =
        "LOWER(r.status) = ?";

    $revenueParams[] =
        strtolower($status);

}

/*
|--------------------------------------------------------------------------
| Revenue Date
|--------------------------------------------------------------------------
*/

if ($fromDate !== '' && $toDate !== '') {

    $revenueWhere[] =
        "DATE(p.paid_at) >= ?";

    $revenueParams[] =
        $fromDate;

    $revenueWhere[] =
        "DATE(p.paid_at) <= ?";

    $revenueParams[] =
        $toDate;
}

$revenueWhereSql =
    implode(' AND ', $revenueWhere);

/*
|--------------------------------------------------------------------------
| Selected Period Revenue
|--------------------------------------------------------------------------
*/

$selectedRevenue = 0;

try {

    $stmt = $db->prepare("
        SELECT COALESCE(SUM(p.amount), 0)
        FROM payments p

        INNER JOIN reservations r
            ON p.booking_id = r.id

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE {$revenueWhereSql}
    ");

    $stmt->execute($revenueParams);

    $selectedRevenue =
        (float) $stmt->fetchColumn();

} catch (PDOException $e) {

    $selectedRevenue = 0;

}

/*
|--------------------------------------------------------------------------
| Today's Bookings
|--------------------------------------------------------------------------
*/

$todayBookings = 0;

try {

    $todayWhere = [
        "DATE(r.created_at) = ?",
        "LOWER(r.status) IN ('confirmed', 'approved')"
    ];

    $todayParams = [
        $today
    ];

    if ($roomType !== '') {

        $todayWhere[] =
            "rm.room_type = ?";

        $todayParams[] =
            $roomType;
    }

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE " .
        implode(' AND ', $todayWhere)
    );

    $stmt->execute($todayParams);

    $todayBookings =
        (int) $stmt->fetchColumn();

} catch (PDOException $e) {

    $todayBookings = 0;

}

/*
|--------------------------------------------------------------------------
| Overall Revenue
|--------------------------------------------------------------------------
|
| This ignores the selected date and room filters.
|
*/

$overallRevenue = 0;

try {

    $overallRevenue = (float) $db
        ->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE LOWER(status) = 'paid'
        ")
        ->fetchColumn();

} catch (PDOException $e) {

    $overallRevenue = 0;

}

/*
|--------------------------------------------------------------------------
| Customer Booking Frequency
|--------------------------------------------------------------------------
*/

$customerReport = [];

try {

    $stmt = $db->prepare("
        SELECT
            u.name,
            u.email,
            COUNT(r.id) AS booking_count

        FROM reservations r

        INNER JOIN users u
            ON r.user_id = u.id

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE {$reservationWhereSql}

        GROUP BY
            u.id,
            u.name,
            u.email

        ORDER BY
            booking_count DESC,
            u.name ASC
    ");

    $stmt->execute($reservationParams);

    $customerReport =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $customerReport = [];

}

/*
|--------------------------------------------------------------------------
| Most Booked Room Types
|--------------------------------------------------------------------------
*/

$roomTypeReport = [];

try {

    $stmt = $db->prepare("
        SELECT
            rm.room_type,
            COUNT(r.id) AS booking_count

        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE {$reservationWhereSql}

        GROUP BY
            rm.room_type

        ORDER BY
            booking_count DESC,
            rm.room_type ASC
    ");

    $stmt->execute($reservationParams);

    $roomTypeReport =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $roomTypeReport = [];

}

/*
|--------------------------------------------------------------------------
| Most Booked Physical Rooms
|--------------------------------------------------------------------------
*/

$roomReport = [];

try {

    $stmt = $db->prepare("
        SELECT
            rm.room_number,
            rm.room_type,
            COUNT(r.id) AS booking_count

        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE {$reservationWhereSql}

        GROUP BY
            rm.id,
            rm.room_number,
            rm.room_type

        ORDER BY
            booking_count DESC,
            rm.room_number ASC
    ");

    $stmt->execute($reservationParams);

    $roomReport =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $roomReport = [];

}

/*
|--------------------------------------------------------------------------
| Reservation Status Breakdown
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Status report does NOT force confirmed/approved.
| It shows all statuses so the admin can see the complete breakdown.
|
*/

$statusReport = [];

$statusWhere = [];
$statusParams = [];

if ($roomType !== '') {

    $statusWhere[] =
        "rm.room_type = ?";

    $statusParams[] =
        $roomType;
}

if ($fromDate !== '' && $toDate !== '') {

    $statusWhere[] =
        "DATE(r.created_at) >= ?";

    $statusParams[] =
        $fromDate;

    $statusWhere[] =
        "DATE(r.created_at) <= ?";

    $statusParams[] =
        $toDate;
}

$statusWhereSql = '';

if (!empty($statusWhere)) {

    $statusWhereSql =
        'WHERE ' .
        implode(' AND ', $statusWhere);
}

try {

    $stmt = $db->prepare("
        SELECT
            r.status,
            COUNT(*) AS total

        FROM reservations r

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        {$statusWhereSql}

        GROUP BY
            r.status

        ORDER BY
            total DESC
    ");

    $stmt->execute($statusParams);

    $statusReport =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $statusReport = [];

}

/*
|--------------------------------------------------------------------------
| Revenue By Date
|--------------------------------------------------------------------------
*/

$dailyRevenueReport = [];

try {

    $stmt = $db->prepare("
        SELECT
            DATE(p.paid_at) AS payment_date,
            COUNT(p.id) AS payment_count,
            COALESCE(SUM(p.amount), 0) AS revenue

        FROM payments p

        INNER JOIN reservations r
            ON p.booking_id = r.id

        INNER JOIN rooms rm
            ON r.room_id = rm.id

        WHERE {$revenueWhereSql}

        GROUP BY
            DATE(p.paid_at)

        ORDER BY
            payment_date DESC
    ");

    $stmt->execute($revenueParams);

    $dailyRevenueReport =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $dailyRevenueReport = [];

}

/*
|--------------------------------------------------------------------------
| Period Label
|--------------------------------------------------------------------------
*/

$periodLabel = 'Overall';

if ($period === 'today') {

    $periodLabel = 'Today';

} elseif ($period === 'month') {

    $periodLabel = 'This Month';

} elseif ($period === 'year') {

    $periodLabel = 'This Year';

} elseif ($period === 'custom') {

    if ($fromDate !== '' && $toDate !== '') {

        $periodLabel =
            $fromDate . ' to ' . $toDate;

    } else {

        $periodLabel =
            'Custom Date - Invalid';

    }

}

/*
|--------------------------------------------------------------------------
| Active Filter Text
|--------------------------------------------------------------------------
*/

$activeFilters = [];

$activeFilters[] =
    'Period: ' . $periodLabel;

if ($roomType !== '') {

    $activeFilters[] =
        'Room: ' . $roomType;

}

if ($status !== '') {

    $activeFilters[] =
        'Status: ' . ucfirst($status);

} else {

    $activeFilters[] =
        'Status: Confirmed / Approved';

}

if ($fromDate !== '' && $toDate !== '') {

    $activeFilters[] =
        'Date: ' . $fromDate . ' → ' . $toDate;

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
        Reports |
        <?= e(
            defined('APP_NAME')
                ? APP_NAME
                : 'LuxeStay'
        ) ?>
    </title>

    <link
        rel="stylesheet"
        href="/public/css/style.css"
    >

    <style>

        .reports-page {
            max-width: 1250px;
            margin: 0 auto;
            padding: 45px 25px 80px;
        }

        .reports-header {
            margin-bottom: 35px;
        }

        .reports-header h1 {
            margin-bottom: 8px;
        }

        .reports-header p {
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Card
        |--------------------------------------------------------------------------
        */

        .filter-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.06);
        }

        .filter-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns:
                repeat(5, minmax(0, 1fr));
            gap: 15px;
        }

        .filter-field label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .filter-field select,
        .filter-field input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            font-size: 14px;
        }

        .filter-field select:focus,
        .filter-field input:focus {
            outline: none;
            border-color: #14213d;
            box-shadow:
                0 0 0 3px rgba(20, 33, 61, 0.08);
        }

        .filter-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-primary {
            background: #14213d;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0f172a;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #14213d;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .filter-note {
            margin-top: 14px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | Active Filters
        |--------------------------------------------------------------------------
        */

        .active-filter-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 30px;
        }

        .active-filter-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .active-filter-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-badge {
            display: inline-block;
            background: #14213d;
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        .report-cards {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            box-shadow:
                0 6px 20px rgba(15, 23, 42, 0.05);
        }

        .report-card .label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .report-card .value {
            font-size: 28px;
            font-weight: 800;
            color: #14213d;
            margin-top: 10px;
        }

        .report-card .description {
            color: #64748b;
            font-size: 12px;
            margin-top: 7px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        .report-section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow:
                0 6px 20px rgba(15, 23, 42, 0.05);
        }

        .report-section h2 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .report-table-wrapper {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            background: #f8fafc;
            padding: 13px;
            text-align: left;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .report-table td {
            padding: 13px;
            border-bottom: 1px solid #edf0f3;
            font-size: 14px;
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 30px !important;
        }

        .revenue-value {
            font-weight: 800;
            color: #198754;
        }

        /*
        |--------------------------------------------------------------------------
        | Custom Date State
        |--------------------------------------------------------------------------
        */

        .custom-date-disabled {
            opacity: .55;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .report-cards {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .report-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .reports-page {
                padding-left: 15px;
                padding-right: 15px;
            }

            .filter-grid,
            .report-cards {
                grid-template-columns: 1fr;
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

<main class="reports-page">

    <!-- HEADER -->

    <section class="reports-header">

        <p class="eyebrow">
            LUXESTAY ADMINISTRATION
        </p>

        <h1>
            Reports & Analytics
        </h1>

        <p>
            Analyze bookings, customers, room popularity,
            reservation status and hotel revenue.
        </p>

    </section>


    <!-- FILTERS -->

    <section class="filter-card">

        <h2>
            Report Filters
        </h2>

        <form
            method="GET"
            action="/views/admin/reports.php"
            id="reportFilterForm"
        >

            <div class="filter-grid">

                <!-- PERIOD -->

                <div class="filter-field">

                    <label for="period">
                        Period
                    </label>

                    <select
                        name="period"
                        id="period"
                    >

                        <option
                            value="overall"
                            <?= $period === 'overall'
                                ? 'selected'
                                : '' ?>
                        >
                            Overall
                        </option>

                        <option
                            value="today"
                            <?= $period === 'today'
                                ? 'selected'
                                : '' ?>
                        >
                            Today
                        </option>

                        <option
                            value="month"
                            <?= $period === 'month'
                                ? 'selected'
                                : '' ?>
                        >
                            This Month
                        </option>

                        <option
                            value="year"
                            <?= $period === 'year'
                                ? 'selected'
                                : '' ?>
                        >
                            This Year
                        </option>

                        <option
                            value="custom"
                            <?= $period === 'custom'
                                ? 'selected'
                                : '' ?>
                        >
                            Custom Date
                        </option>

                    </select>

                </div>


                <!-- ROOM TYPE -->

                <div class="filter-field">

                    <label for="room_type">
                        Room Type
                    </label>

                    <select
                        name="room_type"
                        id="room_type"
                    >

                        <option value="">
                            All Room Types
                        </option>

                        <?php foreach ($roomTypes as $type): ?>

                            <option
                                value="<?= e($type) ?>"
                                <?= $roomType === $type
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="filter-field">

                    <label for="status">
                        Reservation Status
                    </label>

                    <select
                        name="status"
                        id="status"
                    >

                        <option
                            value=""
                            <?= $status === ''
                                ? 'selected'
                                : '' ?>
                        >
                            Confirmed / Approved
                        </option>

                        <option
                            value="pending"
                            <?= $status === 'pending'
                                ? 'selected'
                                : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="confirmed"
                            <?= $status === 'confirmed'
                                ? 'selected'
                                : '' ?>
                        >
                            Confirmed
                        </option>

                        <option
                            value="approved"
                            <?= $status === 'approved'
                                ? 'selected'
                                : '' ?>
                        >
                            Approved
                        </option>

                        <option
                            value="cancelled"
                            <?= $status === 'cancelled'
                                ? 'selected'
                                : '' ?>
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                <!-- FROM -->

                <div
                    class="filter-field"
                    id="fromDateField"
                >

                    <label for="from_date">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        id="from_date"
                        value="<?= e(
                            $period === 'custom'
                                ? $fromDateInput
                                : $fromDate
                        ) ?>"
                    >

                </div>


                <!-- TO -->

                <div
                    class="filter-field"
                    id="toDateField"
                >

                    <label for="to_date">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        id="to_date"
                        value="<?= e(
                            $period === 'custom'
                                ? $toDateInput
                                : $toDate
                        ) ?>"
                    >

                </div>

            </div>


            <div class="filter-buttons">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filters
                </button>

                <a
                    href="/views/admin/reports.php"
                    class="btn btn-secondary"
                >
                    Clear Filters
                </a>

            </div>


            <div class="filter-note">

                <strong>How filtering works:</strong>

                Booking reports use the reservation
                creation date.

                Revenue reports use the payment date.

                The Status filter affects booking and revenue
                results.

            </div>

        </form>

    </section>


    <!-- ACTIVE FILTERS -->

    <section class="active-filter-box">

        <div class="active-filter-title">
            Active Filters
        </div>

        <div class="active-filter-list">

            <?php foreach ($activeFilters as $filter): ?>

                <span class="filter-badge">
                    <?= e($filter) ?>
                </span>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- SUMMARY -->

    <section class="report-cards">

        <article class="report-card">

            <div class="label">
                Bookings — <?= e($periodLabel) ?>
            </div>

            <div class="value">
                <?= number_format(
                    $selectedBookings
                ) ?>
            </div>

            <div class="description">
                Confirmed / approved bookings
                matching the selected filters.
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                Revenue — <?= e($periodLabel) ?>
            </div>

            <div class="value">
                <?= money($selectedRevenue) ?>
            </div>

            <div class="description">
                Successfully paid revenue
                matching the selected filters.
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                Today's Bookings
            </div>

            <div class="value">
                <?= number_format(
                    $todayBookings
                ) ?>
            </div>

            <div class="description">
                Confirmed / approved bookings created today.
            </div>

        </article>


        <article class="report-card">

            <div class="label">
                Overall Revenue
            </div>

            <div class="value">
                <?= money($overallRevenue) ?>
            </div>

            <div class="description">
                All successful payments.
                Not affected by filters.
            </div>

        </article>

    </section>


    <!-- CUSTOMER + ROOM TYPE -->

    <div class="report-grid">

        <!-- CUSTOMER FREQUENCY -->

        <section class="report-section">

            <h2>
                Customer Booking Frequency
            </h2>

            <div class="report-table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Bookings
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($customerReport)): ?>

                        <tr>

                            <td
                                colspan="3"
                                class="empty"
                            >
                                No booking data found
                                for the selected filters.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($customerReport as $customer): ?>

                            <tr>

                                <td>
                                    <?= e(
                                        $customer['name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= e(
                                        $customer['email']
                                    ) ?>
                                </td>

                                <td>

                                    <span class="badge">

                                        <?= number_format(
                                            (int) $customer['booking_count']
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ROOM TYPES -->

        <section class="report-section">

            <h2>
                Most Booked Room Types
            </h2>

            <div class="report-table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>
                                Room Type
                            </th>

                            <th>
                                Bookings
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($roomTypeReport)): ?>

                        <tr>

                            <td
                                colspan="2"
                                class="empty"
                            >
                                No room booking data found
                                for the selected filters.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($roomTypeReport as $room): ?>

                            <tr>

                                <td>
                                    <?= e(
                                        $room['room_type']
                                    ) ?>
                                </td>

                                <td>

                                    <span class="badge">

                                        <?= number_format(
                                            (int) $room['booking_count']
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>


    <!-- PHYSICAL ROOMS -->

    <section class="report-section">

        <h2>
            Most Booked Physical Rooms
        </h2>

        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            Room Number
                        </th>

                        <th>
                            Room Type
                        </th>

                        <th>
                            Total Bookings
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($roomReport)): ?>

                    <tr>

                        <td
                            colspan="3"
                            class="empty"
                        >
                            No room booking data found
                            for the selected filters.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($roomReport as $room): ?>

                        <tr>

                            <td>
                                <?= e(
                                    $room['room_number']
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $room['room_type']
                                ) ?>
                            </td>

                            <td>

                                <span class="badge">

                                    <?= number_format(
                                        (int) $room['booking_count']
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- REVENUE BY DATE -->

    <section class="report-section">

        <h2>
            Revenue by Date
        </h2>

        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            Payment Date
                        </th>

                        <th>
                            Payments
                        </th>

                        <th>
                            Revenue
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($dailyRevenueReport)): ?>

                    <tr>

                        <td
                            colspan="3"
                            class="empty"
                        >
                            No revenue data found
                            for the selected filters.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($dailyRevenueReport as $day): ?>

                        <tr>

                            <td>
                                <?= e(
                                    $day['payment_date']
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (int) $day['payment_count']
                                ) ?>
                            </td>

                            <td class="revenue-value">
                                <?= money(
                                    $day['revenue']
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- STATUS BREAKDOWN -->

    <section class="report-section">

        <h2>
            Reservation Status Breakdown
        </h2>

        <div class="report-table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            Status
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (empty($statusReport)): ?>

                    <tr>

                        <td
                            colspan="2"
                            class="empty"
                        >
                            No reservation data found
                            for the selected filters.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($statusReport as $row): ?>

                        <tr>

                            <td>

                                <span class="badge">

                                    <?= e(
                                        ucfirst(
                                            strtolower(
                                                $row['status']
                                            )
                                        )
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <?= number_format(
                                    (int) $row['total']
                                ) ?>

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


<script>

/*
|--------------------------------------------------------------------------
| Period Filter Helper
|--------------------------------------------------------------------------
|
| Automatically handles the date fields when the admin chooses:
|
| Overall
| Today
| This Month
| This Year
| Custom Date
|
*/

(function () {

    const period =
        document.getElementById('period');

    const fromDate =
        document.getElementById('from_date');

    const toDate =
        document.getElementById('to_date');

    const fromDateField =
        document.getElementById('fromDateField');

    const toDateField =
        document.getElementById('toDateField');

    if (
        !period ||
        !fromDate ||
        !toDate
    ) {
        return;
    }

    function formatDate(date) {

        const year =
            date.getFullYear();

        const month =
            String(
                date.getMonth() + 1
            ).padStart(2, '0');

        const day =
            String(
                date.getDate()
            ).padStart(2, '0');

        return (
            year +
            '-' +
            month +
            '-' +
            day
        );
    }

    function updateDateFields() {

        const selected =
            period.value;

        const now =
            new Date();

        const today =
            formatDate(now);

        /*
        |--------------------------------------------------------------------------
        | Overall
        |--------------------------------------------------------------------------
        */

        if (selected === 'overall') {

            fromDate.value = '';
            toDate.value = '';

            fromDate.disabled = true;
            toDate.disabled = true;

            fromDateField.classList.add(
                'custom-date-disabled'
            );

            toDateField.classList.add(
                'custom-date-disabled'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Today
        |--------------------------------------------------------------------------
        */

        if (selected === 'today') {

            fromDate.value = today;
            toDate.value = today;

            fromDate.disabled = true;
            toDate.disabled = true;

            fromDateField.classList.add(
                'custom-date-disabled'
            );

            toDateField.classList.add(
                'custom-date-disabled'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | This Month
        |--------------------------------------------------------------------------
        */

        if (selected === 'month') {

            const firstDay =
                new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    1
                );

            fromDate.value =
                formatDate(firstDay);

            toDate.value =
                today;

            fromDate.disabled = true;
            toDate.disabled = true;

            fromDateField.classList.add(
                'custom-date-disabled'
            );

            toDateField.classList.add(
                'custom-date-disabled'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | This Year
        |--------------------------------------------------------------------------
        */

        if (selected === 'year') {

            const firstDay =
                new Date(
                    now.getFullYear(),
                    0,
                    1
                );

            fromDate.value =
                formatDate(firstDay);

            toDate.value =
                today;

            fromDate.disabled = true;
            toDate.disabled = true;

            fromDateField.classList.add(
                'custom-date-disabled'
            );

            toDateField.classList.add(
                'custom-date-disabled'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Custom
        |--------------------------------------------------------------------------
        */

        if (selected === 'custom') {

            fromDate.disabled = false;
            toDate.disabled = false;

            fromDateField.classList.remove(
                'custom-date-disabled'
            );

            toDateField.classList.remove(
                'custom-date-disabled'
            );

        }

    }

    period.addEventListener(
        'change',
        updateDateFields
    );

    updateDateFields();

})();

</script>

</body>

</html>