<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/OtpController.php';

$pendingUser = $_SESSION['pending_login_user'] ?? null;

if (!is_array($pendingUser) || empty($pendingUser['id'])) {
    header('Location: /login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otpCode = trim($_POST['otp_code'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otpCode)) {

        $error = 'Please enter a valid 6-digit OTP.';

    } else {

        $verified = (new OtpController())->verifyLoginOtp(
            (int) $pendingUser['id'],
            $otpCode
        );

        if (!$verified) {

            $error = 'Invalid or expired verification code.';

        } else {

            unset($_SESSION['pending_login_user']);

            (new AuthController())->completeLogin($pendingUser);

            header('Location: /index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login | LuxeStay</title>
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>

<div class="auth-container">

    <div class="auth-card">

        <a class="auth-brand" href="/index.php">
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

        <h1>Verify Your Login</h1>

        <p>Enter the 6-digit code sent to your registered email address.</p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label for="otp_code">Verification Code</label>

            <input
                id="otp_code"
                type="text"
                name="otp_code"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                autofocus
            >

            <button type="submit">Verify Login</button>

        </form>

        <p class="bottom-link">
            <a href="/login.php">Return to Login</a>
        </p>

    </div>

</div>

</body>
</html>
