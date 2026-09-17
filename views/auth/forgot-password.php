<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../controllers/OtpController.php';

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = (new User())->findByEmail($email);

        if ($user) {
            $result = (new OtpController())->sendPasswordResetOtp(
                (int) $user['id'],
                $user['email'],
                $user['name']
            );

            if ($result['success']) {
                $_SESSION['password_reset_message'] =
                    'A verification code has been sent.';
                header('Location: /views/auth/verify-reset-otp.php');
                exit;
            }
        }
    }

    $message =
        'If an account exists for this email, a verification code has been sent.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | LuxeStay</title>
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <a class="auth-brand" href="/index.php">
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

        <h1>Forgot Password?</h1>
        <p>Enter your email address to reset your LuxeStay password.</p>

        <?php if ($message !== ''): ?>
            <div class="success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="<?= htmlspecialchars($email) ?>"
                autocomplete="email"
                required
            >
            <button type="submit">Send Verification Code</button>
        </form>

        <p class="bottom-link">
            <a href="/login.php">Return to Login</a>
        </p>
    </div>
</div>
</body>
</html>
