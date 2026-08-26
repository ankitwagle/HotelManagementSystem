<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Guest Access
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(403);
    exit('Invalid user session.');
}

$db = Database::connect();

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Update Profile
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '' || $email === '') {

        $message = 'Name and email are required.';
        $messageType = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';
        $messageType = 'error';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Email
        |--------------------------------------------------------------------------
        */

        $check = $db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
              AND id != ?
            LIMIT 1
        ");

        $check->execute([
            $email,
            $userId
        ]);

        if ($check->fetch()) {

            $message = 'That email address is already in use.';
            $messageType = 'error';

        } else {

            $update = $db->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = ?,
                    address = ?
                WHERE id = ?
            ");

            $update->execute([
                $name,
                $email,
                $phone,
                $address,
                $userId
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Session
            |--------------------------------------------------------------------------
            */

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;

            $message = 'Profile updated successfully.';
            $messageType = 'success';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Get Current Profile
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        address,
        role,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $userId
]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header('Location: /login.php');
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
    My Profile | LuxeStay
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>

<style>

    .profile-page {
        max-width: 850px;
        margin: 40px auto;
        padding: 20px;
    }

    .profile-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 30px;
        box-shadow:
            0 5px 20px rgba(15, 23, 42, 0.06);
    }

    .profile-card h1 {
        color: #102a4c;
        margin-bottom: 8px;
    }

    .form-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-top: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-group label {
        margin-bottom: 7px;
        font-weight: 700;
        color: #102a4c;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 13px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        font: inherit;
    }

    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #8b6f3d;
    }

    .profile-info {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 15px;
        margin-top: 25px;
    }

    .info-box {
        background: #f8fafc;
        padding: 16px;
        border-radius: 9px;
    }

    .info-box span {
        display: block;
        color: #64748b;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .info-box strong {
        color: #102a4c;
    }

    .save-button {
        margin-top: 25px;
        border: none;
        background: #102a4c;
        color: white;
        padding: 12px 20px;
        border-radius: 7px;
        cursor: pointer;
        font-weight: 700;
    }

    .save-button:hover {
        background: #183d68;
    }

    .message {
        margin-top: 20px;
        padding: 14px 17px;
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

    .back-link {
        display: inline-block;
        margin-top: 25px;
        color: #9b7418;
        font-weight: 700;
        text-decoration: none;
    }

    @media (max-width: 650px) {

        .form-grid,
        .profile-info {
            grid-template-columns: 1fr;
        }

        .form-group.full {
            grid-column: auto;
        }

    }

</style>

</head>

<body>

<header>

<div class="logo">

    <a
        href="/index.php"
        style="text-decoration:none;color:inherit;"
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
        🔔 Notifications
    </a>

    <a href="/views/customers/profile.php">
        Profile
    </a>

    <a href="/logout.php">
        Logout
    </a>

</nav>

</header>

<main class="profile-page">

<section class="profile-card">

    <p class="eyebrow">
        GUEST ACCOUNT
    </p>

    <h1>
        My Profile
    </h1>

    <p>
        Update your personal information below.
    </p>

    <?php if ($message !== ''): ?>

        <div
            class="message <?= htmlspecialchars($messageType) ?>"
        >
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-grid">

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars(
                        $customer['name'] ?? ''
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $customer['email'] ?? ''
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="<?= htmlspecialchars(
                        $customer['phone'] ?? ''
                    ) ?>"
                >

            </div>

            <div class="form-group full">

                <label for="address">
                    Address
                </label>

                <textarea
                    id="address"
                    name="address"
                ><?= htmlspecialchars(
                    $customer['address'] ?? ''
                ) ?></textarea>

            </div>

        </div>

        <button
            type="submit"
            name="update_profile"
            class="save-button"
        >
            Save Changes
        </button>

    </form>

    <div class="profile-info">

        <div class="info-box">

            <span>
                Account ID
            </span>

            <strong>
                #<?= (int) $customer['id'] ?>
            </strong>

        </div>

        <div class="info-box">

            <span>
                Account Type
            </span>

            <strong>
                <?= htmlspecialchars(
                    ucfirst($customer['role'])
                ) ?>
            </strong>

        </div>

        <div class="info-box">

            <span>
                Member Since
            </span>

            <strong>
                <?= htmlspecialchars(
                    $customer['created_at'] ?? '-'
                ) ?>
            </strong>

        </div>

    </div>

    <a
        class="back-link"
        href="/index.php"
    >
        ← Back to Home
    </a>

</section>

</main>

</body>

</html>