<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    public function sendOtp(
        string $recipientEmail,
        string $recipientName,
        string $otp
    ): array {

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            // YOUR GMAIL ADDRESS
            $mail->Username = 'ankitwagle5@gmail.com';

            // YOUR GOOGLE APP PASSWORD
            $mail->Password = 'lwoqudpbbevvyclg';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;

            $mail->setFrom(
                'ankitwagle5@gmail.com',
                'LuxeStay Hotel'
            );

            $mail->addAddress(
                $recipientEmail,
                $recipientName
            );

            $mail->isHTML(true);

            $mail->Subject =
                'Your LuxeStay Payment Verification Code';

            $mail->Body = "
                <div style='font-family: Arial, sans-serif;'>
                    <h2>LuxeStay Payment Verification</h2>

                    <p>Hello {$recipientName},</p>

                    <p>
                        Your payment verification OTP is:
                    </p>

                    <h1 style='
                        letter-spacing: 8px;
                        color: #14213d;
                    '>
                        {$otp}
                    </h1>

                    <p>
                        This code expires in 30 seconds.
                    </p>

                    <p>
                        Do not share this code with anyone.
                    </p>

                    <br>

                    <p>
                        LuxeStay Hotel Management System
                    </p>
                </div>
            ";

            $mail->AltBody =
                "Your LuxeStay OTP is: {$otp}. "
                . "This code expires in 10 minutes.";

            $mail->send();

            return [
                'success' => true,
                'message' => 'OTP sent successfully.'
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => 'Email could not be sent.'
            ];
        }
    }
}