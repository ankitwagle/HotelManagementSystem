<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/OtpController.php';
require_once __DIR__ . '/../../models/User.php';

$resetUserId = (int) ($_SESSION['password_reset_user_id'] ?? 0);

if (
    $resetUserId <= 0
    || empty($_SESSION['password_reset_otp_hash'])
) {
    header('Location: /views/auth/forgot-password.php');
    exit;
}

$error = '';
$success = $_SESSION['password_reset_message'] ?? '';
unset($_SESSION['password_reset_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend_password_reset_otp'])) {
        $user = (new User())->findById($resetUserId);

        if (!$user) {
            $error = 'Unable to send a new verification code.';
        } else {
            $result = (new OtpController())->sendPasswordResetOtp(
                $resetUserId,
                $user['email'],
                $user['name'],
                true
            );

            if ($result['success']) {
                $success = 'A new verification code has been sent.';
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $otpCode = trim($_POST['otp_code'] ?? '');

        if (!preg_match('/^[0-9]{6}$/', $otpCode)) {
            $error = 'Please enter a valid 6-digit OTP.';
        } elseif (!(new OtpController())->verifyPasswordResetOtp(
            $resetUserId,
            $otpCode
        )) {
            $error = 'Invalid or expired verification code.';
        } else {
            header('Location: /views/auth/reset-password.php');
            exit;
        }
    }
}

$resetOtpLastSent = (int) (
    $_SESSION['password_reset_otp_last_sent'] ?? 0
);
$resetResendRemaining = max(
    0,
    60 - (time() - $resetOtpLastSent)
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password Reset | LuxeStay</title>
    <link rel="stylesheet" href="../../public/css/auth.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <a class="auth-brand" href="/index.php">
            <span class="logo-icon">✦</span>
            Luxe<span>Stay</span>
        </a>

        <h1>Verify Password Reset</h1>
        <p>Enter the 6-digit code sent to your email address.</p>

        <?php if ($success !== ''): ?>
            <div class="success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

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
            <button type="submit">Verify Code</button>
        </form>

        <form method="POST" action="">
            <input
                type="hidden"
                name="resend_password_reset_otp"
                value="1"
            >
            <button
                id="resend-password-reset-otp"
                type="submit"
                <?= $resetResendRemaining > 0 ? 'disabled' : '' ?>
            >
                Resend OTP<?= $resetResendRemaining > 0
                    ? ' in ' . $resetResendRemaining . 's'
                    : '' ?>
            </button>
        </form>

        <p class="bottom-link">
            <a href="/login.php">Return to Login</a>
        </p>
    </div>
</div>

<script>
    (function () {
        const button = document.getElementById(
            'resend-password-reset-otp'
        );
        let remaining = <?= (int) $resetResendRemaining ?>;

        if (!button || remaining <= 0) {
            return;
        }

        const update = function () {
            if (remaining <= 0) {
                button.disabled = false;
                button.textContent = 'Resend OTP';
                return;
            }

            button.disabled = true;
            button.textContent =
                'Resend OTP in ' + remaining + 's';
            remaining--;
            window.setTimeout(update, 1000);
        };

        update();
    }());
</script>

</body>
</html>
