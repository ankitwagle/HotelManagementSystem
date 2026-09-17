<?php

require_once __DIR__ . '/../models/Otp.php';
require_once __DIR__ . '/../services/MailService.php';

class OtpController
{
    private Otp $otpModel;
    private MailService $mailService;

    public function __construct()
    {
        $this->otpModel = new Otp();
        $this->mailService = new MailService();
    }

    public function sendPaymentOtp(
        int $userId,
        int $reservationId,
        string $email,
        string $name
    ): array {

        return $this->sendOtp(
            $userId,
            $reservationId,
            $email,
            $name,
            'Your LuxeStay Payment Verification Code',
            'LuxeStay Payment Verification',
            'To continue with your payment, please use the following verification code:'
        );
    }

    public function sendLoginOtp(
        int $userId,
        string $email,
        string $name,
        bool $isResend = false
    ): array {

        if ($isResend) {
            $remaining = $this->getCooldownRemaining(
                'login_otp_last_sent'
            );

            if ($remaining > 0) {
                return [
                    'success' => false,
                    'message' =>
                        'Please wait ' . $remaining .
                        ' seconds before requesting another code.'
                ];
            }
        }

        return $this->sendOtp(
            $userId,
            null,
            $email,
            $name,
            'Your LuxeStay Login Verification Code',
            'LuxeStay Login Verification',
            'To complete your login, please use the following verification code:'
        );
    }

    public function sendPasswordResetOtp(
        int $userId,
        string $email,
        string $name,
        bool $isResend = false
    ): array {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($isResend) {
            $remaining = $this->getCooldownRemaining(
                'password_reset_otp_last_sent'
            );

            if ($remaining > 0) {
                return [
                    'success' => false,
                    'message' =>
                        'Please wait ' . $remaining .
                        ' seconds before requesting another code.'
                ];
            }
        }

        $otpCode = (string) random_int(
            100000,
            999999
        );

        unset($_SESSION['password_reset_authorized']);
        $this->clearPasswordResetSession();

        $_SESSION['password_reset_otp_hash'] =
            password_hash($otpCode, PASSWORD_DEFAULT);
        $_SESSION['password_reset_user_id'] = $userId;
        $_SESSION['password_reset_expires'] = time() + 30;
        $_SESSION['password_reset_attempts'] = 0;

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

        $body = "
            <div style='
                font-family: Arial, sans-serif;
                line-height: 1.6;
            '>
                <h2>LuxeStay Password Reset</h2>
                <p>Hello {$safeName},</p>
                <p>To reset your LuxeStay password, please use the following verification code:</p>
                <div style='
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    padding: 15px;
                    background: #f3f4f6;
                    display: inline-block;
                    border-radius: 8px;
                    color: #14213d;
                '>
                    {$otpCode}
                </div>
                <p>This OTP expires in <strong>30 seconds</strong>.</p>
                <p>You have a maximum of <strong>5 verification attempts</strong>.</p>
                <p>Do not share this code with anyone.</p>
                <br>
                <p><strong>LuxeStay Hotel</strong></p>
            </div>
        ";

        if (!$this->mailService->send(
            $email,
            $name,
            'Your LuxeStay Password Reset Code',
            $body
        )) {
            $this->clearPasswordResetSession();

            return [
                'success' => false,
                'message' => 'Unable to send OTP email.'
            ];
        }

        $_SESSION['password_reset_otp_last_sent'] = time();

        return [
            'success' => true,
            'message' => 'Verification code sent successfully.'
        ];
    }

    private function sendOtp(
        int $userId,
        ?int $reservationId,
        string $email,
        string $name,
        string $subject,
        string $heading,
        string $instruction
    ): array {

        /*
         * Generate a cryptographically secure
         * six-digit OTP.
         */
        $otpCode = (string) random_int(
            100000,
            999999
        );

        if ($reservationId === null) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $this->clearLoginOtpSession();

            $_SESSION['login_otp_hash'] =
                password_hash($otpCode, PASSWORD_DEFAULT);
            $_SESSION['login_otp_user_id'] = $userId;
            $_SESSION['login_otp_expires'] = time() + 30;
            $_SESSION['login_otp_attempts'] = 0;
        } else {
            /*
             * Store only the hashed OTP in the database.
             */
            $saved = $this->otpModel->createOtp(
                $userId,
                $reservationId,
                $otpCode
            );

            if (!$saved) {
                return [
                    'success' => false,
                    'message' => 'Unable to generate verification code.'
                ];
            }
        }

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

        $body = "
            <div style='
                font-family: Arial, sans-serif;
                line-height: 1.6;
            '>

                <h2>
                    {$heading}
                </h2>

                <p>
                    Hello {$safeName},
                </p>

                <p>
                    {$instruction}
                </p>

                <div style='
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    padding: 15px;
                    background: #f3f4f6;
                    display: inline-block;
                    border-radius: 8px;
                    color: #14213d;
                '>
                    {$otpCode}
                </div>

                <p>
                    This OTP expires in
                    <strong>30 seconds</strong>.
                </p>

                <p>
                    You have a maximum of
                    <strong>5 verification attempts</strong>.
                </p>

                <p>
                    Do not share this code with anyone.
                </p>

                <br>

                <p>
                    <strong>LuxeStay Hotel</strong>
                </p>

            </div>
        ";

        $sent = $this->mailService->send(
            $email,
            $name,
            $subject,
            $body
        );

        if (!$sent) {
            if ($reservationId === null) {
                $this->clearLoginOtpSession();
            }

            return [
                'success' => false,
                'message' => 'Unable to send OTP email.'
            ];
        }

        if ($reservationId === null) {
            $_SESSION['login_otp_last_sent'] = time();
        }

        return [
            'success' => true,
            'message' => 'Verification code sent successfully.'
        ];
    }


    public function verifyPaymentOtp(
        int $userId,
        int $reservationId,
        string $otpCode
    ): bool {

        return $this->otpModel->verifyOtp(
            $userId,
            $reservationId,
            trim($otpCode)
        );
    }

    public function verifyLoginOtp(
        int $userId,
        string $otpCode
    ): bool {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            !isset(
                $_SESSION['login_otp_hash'],
                $_SESSION['login_otp_user_id'],
                $_SESSION['login_otp_expires'],
                $_SESSION['login_otp_attempts']
            )
            || (int) $_SESSION['login_otp_user_id'] !== $userId
        ) {
            return false;
        }

        if ((int) $_SESSION['login_otp_expires'] < time()) {
            $this->clearLoginOtpSession();
            return false;
        }

        if ((int) $_SESSION['login_otp_attempts'] >= 5) {
            $this->clearLoginOtpSession();
            return false;
        }

        $isValid = password_verify(
            trim($otpCode),
            (string) $_SESSION['login_otp_hash']
        );

        if ($isValid) {
            $this->clearLoginOtpSession();
            return true;
        }

        $_SESSION['login_otp_attempts']++;

        if ((int) $_SESSION['login_otp_attempts'] >= 5) {
            $this->clearLoginOtpSession();
        }

        return false;
    }

    public function verifyPasswordResetOtp(
        int $userId,
        string $otpCode
    ): bool {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            !isset(
                $_SESSION['password_reset_otp_hash'],
                $_SESSION['password_reset_user_id'],
                $_SESSION['password_reset_expires'],
                $_SESSION['password_reset_attempts']
            )
            || (int) $_SESSION['password_reset_user_id'] !== $userId
        ) {
            return false;
        }

        if ((int) $_SESSION['password_reset_expires'] < time()) {
            $this->clearPasswordResetSession();
            return false;
        }

        if ((int) $_SESSION['password_reset_attempts'] >= 5) {
            $this->clearPasswordResetSession();
            return false;
        }

        if (password_verify(
            trim($otpCode),
            (string) $_SESSION['password_reset_otp_hash']
        )) {
            $this->clearPasswordResetSession();
            $_SESSION['password_reset_authorized'] = true;
            $_SESSION['password_reset_user_id'] = $userId;
            return true;
        }

        $_SESSION['password_reset_attempts']++;

        if ((int) $_SESSION['password_reset_attempts'] >= 5) {
            $this->clearPasswordResetSession();
        }

        return false;
    }

    private function clearPasswordResetSession(): void
    {
        unset(
            $_SESSION['password_reset_otp_hash'],
            $_SESSION['password_reset_user_id'],
            $_SESSION['password_reset_expires'],
            $_SESSION['password_reset_attempts']
        );
    }

    private function getCooldownRemaining(string $sessionKey): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lastSent = (int) ($_SESSION[$sessionKey] ?? 0);

        return max(0, 60 - (time() - $lastSent));
    }

    private function clearLoginOtpSession(): void
    {
        unset(
            $_SESSION['login_otp_hash'],
            $_SESSION['login_otp_user_id'],
            $_SESSION['login_otp_expires'],
            $_SESSION['login_otp_attempts']
        );
    }
}

?>