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

        // Generate secure 6-digit OTP
        $otpCode = (string) random_int(
            100000,
            999999
        );

        // Save OTP in database
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

        $subject =
            'Your LuxeStay Payment Verification Code';

        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>

                <h2>LuxeStay Payment Verification</h2>

                <p>
                    Hello " . htmlspecialchars($name) . ",
                </p>

                <p>
                    To continue with your payment, please use
                    the following verification code:
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
                    This OTP expires in <strong>30 seconds</strong>.
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
}

?>