<?php

require_once __DIR__ . '/../models/Payment.php';

class PaymentController
{
    private Payment $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
    }

    /**
     * Get a confirmed reservation that is eligible for payment.
     */
    public function getReservationForPayment(
        int $reservationId,
        int $userId
    ): array {

        if ($reservationId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid reservation.'
            ];
        }

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user.'
            ];
        }

        $reservation =
            $this->paymentModel->getReservationForPayment(
                $reservationId,
                $userId
            );

        if (!$reservation) {
            return [
                'success' => false,
                'message' =>
                    'This reservation is not available for payment.'
            ];
        }

        return [
            'success' => true,
            'reservation' => $reservation
        ];
    }

    /**
     * Start the payment process.
     *
     * Generates a six-digit OTP and stores it temporarily
     * in the PHP session.
     */
    public function startPayment(
        int $reservationId,
        int $userId
    ): array {

        $result = $this->getReservationForPayment(
            $reservationId,
            $userId
        );

        if (!$result['success']) {
            return $result;
        }

        $reservation = $result['reservation'];

        /*
         * Do not allow a second payment for the same reservation.
         */
        $existingPayment =
            $this->paymentModel->getPaymentByReservation(
                $reservationId
            );

        if (
            $existingPayment &&
            strtolower(
                (string) ($existingPayment['status'] ?? '')
            ) === 'paid'
        ) {
            return [
                'success' => false,
                'message' =>
                    'This reservation has already been paid.'
            ];
        }

        $checkIn = new DateTime(
            $reservation['check_in']
        );

        $checkOut = new DateTime(
            $reservation['check_out']
        );

        $nights = $checkIn->diff($checkOut)->days;

        if ($nights < 1) {
            $nights = 1;
        }

        $pricePerNight =
            (float) $reservation['price'];

        $totalAmount =
            $nights * $pricePerNight;

        /*
         * Generate a secure six-digit OTP.
         */
        $otp = (string) random_int(
            100000,
            999999
        );

        /*
         * Store only the hash in the session.
         */
        $_SESSION['payment_otp_hash'] =
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

        $_SESSION['payment_otp_expires'] =
            time() + (10 * 60);

        $_SESSION['payment_reservation_id'] =
            $reservationId;

        $_SESSION['payment_user_id'] =
            $userId;

        $_SESSION['payment_amount'] =
            $totalAmount;

        /*
         * Customer information.
         */
        $email =
            $reservation['customer_email'];

        $customerName =
            $reservation['customer_name'];

        $subject =
            'LuxeStay Payment Verification Code';

        $message = "
Dear {$customerName},

Your LuxeStay payment verification code is:

{$otp}

This code will expire in 10 minutes.

Reservation #{$reservationId}

Amount: $" .
            number_format(
                $totalAmount,
                2
            ) .
            "

If you did not request this payment,
please ignore this email.

Regards,
LuxeStay Hotel
";

        $headers = [];

        $headers[] =
            'From: LuxeStay <noreply@luxestay.com>';

        $headers[] =
            'Reply-To: noreply@luxestay.com';

        $headers[] =
            'Content-Type: text/plain; charset=UTF-8';

        /*
         * WAMP LOCAL DEVELOPMENT
         *
         * mail() may fail because localhost SMTP is not configured.
         *
         * The @ suppresses the PHP warning.
         *
         * The OTP is also returned so the payment system
         * can be tested locally without real email.
         */
        $mailSent = @mail(
            $email,
            $subject,
            $message,
            implode(
                "\r\n",
                $headers
            )
        );

        if ($mailSent) {

            return [
                'success' => true,
                'message' =>
                    'A verification code has been sent to your email address.',
                'email' => $email,
                'amount' => $totalAmount,
                'mail_sent' => true,
                'otp' => $otp
            ];

        }

        /*
         * Local WAMP fallback.
         */
        return [
            'success' => true,
            'message' =>
                'Email is not configured on this WAMP server. Use the verification code shown below.',
            'email' => $email,
            'amount' => $totalAmount,
            'mail_sent' => false,
            'otp' => $otp
        ];
    }

    /**
     * Verify the OTP and create the payment record.
     */
    public function verifyOtp(
        int $userId,
        string $otp
    ): array {

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user.'
            ];
        }

        $otp = trim($otp);

        if (!preg_match('/^\d{6}$/', $otp)) {
            return [
                'success' => false,
                'message' =>
                    'Please enter the six-digit verification code.'
            ];
        }

        if (
            !isset(
                $_SESSION['payment_otp_hash'],
                $_SESSION['payment_otp_expires'],
                $_SESSION['payment_reservation_id'],
                $_SESSION['payment_user_id'],
                $_SESSION['payment_amount']
            )
        ) {
            return [
                'success' => false,
                'message' =>
                    'Your payment verification session has expired. Please start again.'
            ];
        }

        if (
            (int) $_SESSION['payment_user_id']
            !== $userId
        ) {
            return [
                'success' => false,
                'message' =>
                    'Payment verification is not available for this account.'
            ];
        }

        if (
            time()
            > (int) $_SESSION['payment_otp_expires']
        ) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' =>
                    'Your verification code has expired. Please request a new code.'
            ];
        }

        if (
            !password_verify(
                $otp,
                $_SESSION['payment_otp_hash']
            )
        ) {
            return [
                'success' => false,
                'message' =>
                    'Incorrect verification code.'
            ];
        }

        $reservationId =
            (int) $_SESSION['payment_reservation_id'];

        $amount =
            (float) $_SESSION['payment_amount'];

        /*
         * Verify the reservation again before inserting
         * the payment.
         */
        $reservationResult =
            $this->getReservationForPayment(
                $reservationId,
                $userId
            );

        if (!$reservationResult['success']) {

            $this->clearOtpSession();

            return [
                'success' => false,
                'message' =>
                    'The reservation is no longer available for payment.'
            ];
        }

        /*
         * Prevent duplicate payments.
         */
        $existingPayment =
            $this->paymentModel->getPaymentByReservation(
                $reservationId
            );

        if (
            $existingPayment &&
            strtolower(
                (string) ($existingPayment['status'] ?? '')
            ) === 'paid'
        ) {

            $this->clearOtpSession();

            return [
                'success' => false,
                'message' =>
                    'This reservation has already been paid.'
            ];
        }

        /*
         * Create a unique transaction reference.
         */
        $transactionReference =
            'LUXE-' .
            date('YmdHis') .
            '-' .
            strtoupper(
                bin2hex(
                    random_bytes(3)
                )
            );

        try {

            $paymentId =
                $this->paymentModel->createPayment(
                    $reservationId,
                    $amount,
                    'OTP',
                    $transactionReference
                );

            $this->clearOtpSession();

            return [
                'success' => true,
                'id' => $paymentId,
                'reservation_id' => $reservationId,
                'amount' => $amount,
                'transaction_reference' =>
                    $transactionReference,
                'message' =>
                    'Payment completed successfully.'
            ];

        } catch (PDOException $e) {

            return [
                'success' => false,
                'message' =>
                    'Database error while creating payment.'
            ];
        }
    }

    /**
     * Get the user's payment history.
     */
    public function userPayments(
        int $userId
    ): array {

        if ($userId <= 0) {
            return [];
        }

        return $this->paymentModel->getUserPayments(
            $userId
        );
    }

    /**
     * Remove temporary OTP information.
     */
    private function clearOtpSession(): void
    {
        unset(
            $_SESSION['payment_otp_hash'],
            $_SESSION['payment_otp_expires'],
            $_SESSION['payment_reservation_id'],
            $_SESSION['payment_user_id'],
            $_SESSION['payment_amount']
        );
    }
}
?>