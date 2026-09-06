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

if (
    !isset($_SESSION['user']) ||
    ($_SESSION['user']['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit('Access denied. Admin access required.');
}

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$db = Database::connect();

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Customer Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| Delete Customer
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_customer'])
) {

    $customerId = (int) ($_POST['customer_id'] ?? 0);

    if ($customerId <= 0) {

        $message = 'Invalid customer.';
        $messageType = 'error';

    } else {

        /*
        |----------------------------------------------------------------------
        | Confirm that the account is a customer
        |----------------------------------------------------------------------
        */

        $check = $db->prepare("
            SELECT
                id,
                name
            FROM users
            WHERE id = ?
              AND role = 'guest'
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $check->execute([
            $customerId
        ]);

        $customer = $check->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {

            $message = 'Customer not found.';
            $messageType = 'error';

        } else {

            /*
            |------------------------------------------------------------------
            | Check for existing reservations
            |------------------------------------------------------------------
            */

            $reservationCheck = $db->prepare("
                SELECT COUNT(*)
                FROM reservations
                WHERE user_id = ?
            ");

            $reservationCheck->execute([
                $customerId
            ]);

            $reservationCount =
                (int) $reservationCheck->fetchColumn();

            if ($reservationCount > 0) {

                $message =
                    'Cannot delete ' .
                    $customer['name'] .
                    ' because this customer has existing reservations.';

                $messageType = 'error';

            } else {

                /*
                |------------------------------------------------------------------
                | Soft Delete Customer
                |------------------------------------------------------------------
                */

                $delete = $db->prepare("
                    UPDATE users
                    SET deleted_at = NOW()
                    WHERE id = ?
                      AND role = 'guest'
                      AND deleted_at IS NULL
                ");

                $delete->execute([
                    $customerId
                ]);

                if ($delete->rowCount() > 0) {

                    $message =
                        'Customer "' .
                        $customer['name'] .
                        '" deleted successfully.';

                    $messageType = 'success';

                } else {

                    $message =
                        'Customer could not be deleted.';

                    $messageType = 'error';
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Get Customers
|--------------------------------------------------------------------------
|
| Search by:
| - Name
| - Email
| - Phone
| - Address
|
*/

if ($search !== '') {

    $customerStmt = $db->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            address,
            role,
            created_at
        FROM users
        WHERE role = 'guest'
          AND deleted_at IS NULL
          AND (
                name LIKE ?
                OR email LIKE ?
                OR phone LIKE ?
                OR address LIKE ?
          )
        ORDER BY id DESC
    ");

    $searchTerm = '%' . $search . '%';

    $customerStmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);

} else {

    $customerStmt = $db->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            address,
            role,
            created_at
        FROM users
        WHERE role = 'guest'
          AND deleted_at IS NULL
        ORDER BY id DESC
    ");

    $customerStmt->execute();
}

$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

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
    Customers | LuxeStay Admin
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .admin-page {
        max-width: 1300px;
        margin: 40px auto;
        padding: 20px;
    }

    .admin-page h1 {
        color: #102a4c;
    }

    /*
    |----------------------------------------------------------------------
    | Search Box
    |----------------------------------------------------------------------
    */

    .customer-search {
        margin-top: 25px;
        background: #ffffff;
        padding: 20px;
        border-radius: 12px;
        box-shadow:
            0 5px 20px rgba(0, 0, 0, .08);
    }

    .search-form {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .search-input {
        flex: 1;
        min-width: 0;
        padding: 13px 16px;
        border: 1px solid #d5dbe3;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
        transition: border-color .2s ease,
                    box-shadow .2s ease;
    }

    .search-input:focus {
        border-color: #9b7418;
        box-shadow:
            0 0 0 3px rgba(155, 116, 24, .12);
    }

    .search-button {
        background: #102a4c;
        color: #ffffff;
        border: none;
        padding: 13px 22px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        white-space: nowrap;
    }

    .search-button:hover {
        background: #173b68;
    }

    .clear-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 13px 18px;
        border-radius: 8px;
        background: #eef1f5;
        color: #102a4c;
        text-decoration: none;
        font-weight: 700;
        white-space: nowrap;
    }

    .clear-button:hover {
        background: #e1e6ec;
    }

    .search-result {
        margin-top: 12px;
        color: #64748b;
        font-size: 14px;
    }

    /*
    |----------------------------------------------------------------------
    | Table
    |----------------------------------------------------------------------
    */

    .table-container {
        margin-top: 30px;
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow:
            0 5px 20px rgba(0, 0, 0, .08);
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #102a4c;
        color: white;
    }

    /*
    |----------------------------------------------------------------------
    | Messages
    |----------------------------------------------------------------------
    */

    .message {
        margin-top: 20px;
        padding: 15px 18px;
        border-radius: 8px;
        font-weight: 600;
    }

    .message.success {
        background: #e8f7ee;
        color: #176b3a;
        border: 1px solid #a9dfbd;
    }

    .message.error {
        background: #fdecec;
        color: #9b1c1c;
        border: 1px solid #efb1b1;
    }

    /*
    |----------------------------------------------------------------------
    | Delete Button
    |----------------------------------------------------------------------
    */

    .delete-button {
        background: #b42318;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .delete-button:hover {
        background: #8f1c13;
    }

    /*
    |----------------------------------------------------------------------
    | Back Link
    |----------------------------------------------------------------------
    */

    .back {
        display: inline-block;
        margin-top: 20px;
        color: #9b7418;
        font-weight: bold;
        text-decoration: none;
    }

    /*
    |----------------------------------------------------------------------
    | Empty State
    |----------------------------------------------------------------------
    */

    .empty {
        text-align: center;
        padding: 30px;
        color: #64748b;
    }

    /*
    |----------------------------------------------------------------------
    | Mobile
    |----------------------------------------------------------------------
    */

    @media (max-width: 700px) {

        .search-form {
            flex-direction: column;
            align-items: stretch;
        }

        .search-button,
        .clear-button {
            width: 100%;
            text-align: center;
        }

        .admin-page {
            margin-top: 20px;
            padding: 15px;
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

<main class="admin-page">


<p class="eyebrow">
    CUSTOMER MANAGEMENT
</p>

<h1>
    Customers
</h1>

<p>
    View and manage all registered hotel customers.
</p>


<?php if ($message !== ''): ?>

    <div
        class="message <?= htmlspecialchars($messageType) ?>"
    >

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<!-- CUSTOMER SEARCH -->

<section class="customer-search">

    <form
        method="GET"
        action="/views/admin/customers.php"
        class="search-form"
    >

        <input
            type="search"
            name="search"
            class="search-input"
            placeholder="Search customer by name, email, phone, or address..."
            value="<?= htmlspecialchars($search) ?>"
            autocomplete="off"
        >

        <button
            type="submit"
            class="search-button"
        >
            🔎 Search
        </button>

        <?php if ($search !== ''): ?>

            <a
                href="/views/admin/customers.php"
                class="clear-button"
            >
                Clear
            </a>

        <?php endif; ?>

    </form>

    <?php if ($search !== ''): ?>

        <div class="search-result">

            Showing results for:
            <strong>
                <?= htmlspecialchars($search) ?>
            </strong>

            —
            <?= count($customers) ?>
            customer(s) found.

        </div>

    <?php endif; ?>

</section>


<!-- CUSTOMER TABLE -->

<div class="table-container">

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Name</th>

                <th>Email</th>

                <th>Phone</th>

                <th>Address</th>

                <th>Role</th>

                <th>Registered</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

        <?php if (!$customers): ?>

            <tr>

                <td
                    colspan="8"
                    class="empty"
                >

                    <?php if ($search !== ''): ?>

                        No customers matched
                        "<strong>
                            <?= htmlspecialchars($search) ?>
                        </strong>".

                    <?php else: ?>

                        No customers found.

                    <?php endif; ?>

                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($customers as $customer): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars(
                            $customer['id']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['name']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['email']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['phone'] ?? '-'
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['address'] ?? '-'
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['role']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $customer['created_at']
                        ) ?>
                    </td>

                    <td>

                        <form
                            method="POST"
                            onsubmit="return confirm(
                                'Are you sure you want to delete this customer?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="customer_id"
                                value="<?= (int) $customer['id'] ?>"
                            >

                            <button
                                type="submit"
                                name="delete_customer"
                                class="delete-button"
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>


<a
    class="back"
    href="/views/admin/index.php"
>
    ← Back to Admin Dashboard
</a>


</main>

</body>

</html>
