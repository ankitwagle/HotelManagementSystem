<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

$resetUserId = (int) ($_SESSION['password_reset_user_id'] ?? 0);

if (
    empty($_SESSION['password_reset_authorized'])
    || $resetUserId <= 0
) {
    header('Location: /login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password must contain at least one special character.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ((new User())->updatePassword(
            $resetUserId,
            $hashedPassword
        )) {
            unset(
                $_SESSION['password_reset_authorized'],
                $_SESSION['password_reset_user_id'],
                $_SESSION['password_reset_otp_hash'],
                $_SESSION['password_reset_expires'],
                $_SESSION['password_reset_attempts'],
                $_SESSION['password_reset_message']
            );

            $_SESSION['auth_success_message'] =
                'Your password has been reset successfully.';

            header('Location: /login.php');
            exit;
        }

        $error = 'Unable to reset your password. Please try again.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | LuxeStay</title>
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <a class="auth-brand" href="/index.php">
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

        <h1>Reset Password</h1>
        <p>Create a new password for your LuxeStay account.</p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="password">New Password</label>
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                required
            >

            <div class="password-requirements">
                <small>Password requirements:</small>
                <ul>
                    <li>At least 8 characters</li>
                    <li>One uppercase letter</li>
                    <li>One lowercase letter</li>
                    <li>One number</li>
                    <li>One special character</li>
                </ul>
            </div>

            <label for="confirm_password">Confirm Password</label>
            <input
                id="confirm_password"
                type="password"
                name="confirm_password"
                autocomplete="new-password"
                required
            >

            <button type="submit">Reset Password</button>
        </form>

        <p class="bottom-link">
            <a href="/login.php">Return to Login</a>
        </p>
    </div>
</div>
</body>
</html>
