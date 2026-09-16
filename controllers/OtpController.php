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
        string $name
    ): array {

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
            return [
                'success' => false,
                'message' => 'Unable to send OTP email.'
            ];
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

        return $this->otpModel->verifyOtp(
            $userId,
            null,
            trim($otpCode)
        );
    }
}

?>