<?php

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/NotificationController.php';

class PaymentController
{
    private Payment $paymentModel;
    private NotificationController $notificationController;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->notificationController =
            new NotificationController();
    }

    /**
     * Get a confirmed reservation that belongs to the logged-in user.
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
     * Get the latest payment for a reservation.
     */
    public function getPaymentByReservation(
        int $reservationId
    ): ?array {

        if ($reservationId <= 0) {
            return null;
        }

        return $this->paymentModel->getPaymentByReservation(
            $reservationId
        );
    }

    /**
     * Process a direct payment.
     *
     * OTP/email verification is intentionally not used here.
     */
    public function processPayment(
        int $reservationId,
        int $userId
    ): array {

        /*
         * Verify reservation.
         */
        $reservationResult =
            $this->getReservationForPayment(
                $reservationId,
                $userId
            );

        if (!$reservationResult['success']) {
            return $reservationResult;
        }

        $reservation =
            $reservationResult['reservation'];

        /*
         * Check whether a payment already exists.
         */
        $existingPayment =
            $this->paymentModel->getPaymentByReservation(
                $reservationId
            );

        if ($existingPayment) {

            $status =
                strtolower(
                    trim(
                        (string) (
                            $existingPayment['status']
                            ?? ''
                        )
                    )
                );

            if ($status === 'paid') {

                return [
                    'success' => false,
                    'message' =>
                        'This reservation has already been paid.'
                ];
            }
        }

        /*
         * Calculate number of nights.
         */
        try {

            $checkIn = new DateTime(
                $reservation['check_in']
            );

            $checkOut = new DateTime(
                $reservation['check_out']
            );

            $nights =
                $checkIn->diff($checkOut)->days;

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' =>
                    'Invalid reservation dates.'
            ];
        }

        if ($nights < 1) {
            $nights = 1;
        }

        /*
         * Calculate total payment.
         */
        $pricePerNight =
            (float) $reservation['price'];

        $totalAmount =
            $nights * $pricePerNight;

        if ($totalAmount <= 0) {

            return [
                'success' => false,
                'message' =>
                    'Invalid payment amount.'
            ];
        }

        /*
         * Generate transaction reference.
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

        /*
         * Create payment.
         */
        try {

            $paymentId =
                $this->paymentModel->createPayment(
                    $reservationId,
                    $totalAmount,
                    'Direct Payment',
                    $transactionReference
                );

            /*
             * Create guest notification
             * after successful payment.
             */
            try {

                $this->notificationController->create(
                    $userId,
                    'Payment Successful',
                    'Your payment of $' .
                    number_format(
                        $totalAmount,
                        2
                    ) .
                    ' for reservation #' .
                    $reservationId .
                    ' was completed successfully. ' .
                    'Transaction reference: ' .
                    $transactionReference . '.'
                );

            } catch (Exception $notificationError) {

                /*
                 * Do not fail the payment if the
                 * notification cannot be created.
                 */
            }

            return [
                'success' => true,
                'id' => $paymentId,
                'reservation_id' => $reservationId,
                'amount' => $totalAmount,
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
     * Get all payments belonging to a user.
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
}
?>
