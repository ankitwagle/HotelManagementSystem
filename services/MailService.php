<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function send(
        string $email,
        string $name,
        string $subject,
        string $body
    ): bool {

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            /*
             * PUT YOUR GMAIL ADDRESS HERE
             */
            $mail->Username = 'ankitwagle5@gmail.com';

            /*
             * PUT THE 16-DIGIT GOOGLE APP PASSWORD HERE
             */
            $mail->Password = 'lwoqudpbbevvyclg';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;

            $mail->setFrom(
                'ankitwagle5@gmail.com',
                'LuxeStay'
            );

            $mail->addAddress(
                $email,
                $name
            );

            $mail->isHTML(true);

            $mail->Subject = $subject;

            $mail->Body = $body;

            $mail->AltBody =
                strip_tags($body);

            $mail->send();

            return true;

        } catch (Exception $e) {

            error_log(
                'Mail Error: ' .
                $mail->ErrorInfo
            );

            return false;
        }
    }
}