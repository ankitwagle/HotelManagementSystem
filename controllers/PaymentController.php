<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../models/Payment.php';

require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\StripeClient;

class PaymentController
{
    private Payment $paymentModel;
    private StripeClient $stripe;

    public function __construct()
    {
        $this->paymentModel = new Payment();

        $stripeConfig = require __DIR__ . '/../config/stripe.php';

        if (
            empty($stripeConfig['secret_key']) ||
            !str_starts_with(
                $stripeConfig['secret_key'],
                'sk_test_'
            )
        ) {
            throw new Exception(
                'Stripe test secret key is not configured correctly.'
            );
        }

        $this->stripe = new StripeClient(
            $stripeConfig['secret_key']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get reservation for payment
    |--------------------------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | Get existing payment
    |--------------------------------------------------------------------------
    */

    public function getPaymentByReservation(
        int $reservationId
    ): ?array {

        if ($reservationId <= 0) {
            return null;
        }

        return $this->paymentModel
            ->getPaymentByReservation($reservationId);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Stripe Checkout Session
    |--------------------------------------------------------------------------
    */

    public function createCheckoutSession(
        int $reservationId,
        int $userId
    ): array {

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
        |--------------------------------------------------------------------------
        | Prevent payment if already paid
        |--------------------------------------------------------------------------
        */

        $existingPayment =
            $this->paymentModel
                ->getPaymentByReservation(
                    $reservationId
                );

        if ($existingPayment) {

            $status = strtolower(
                trim(
                    (string)(
                        $existingPayment['status'] ?? ''
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
        |--------------------------------------------------------------------------
        | Calculate number of nights
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Calculate total
        |--------------------------------------------------------------------------
        */

        $pricePerNight =
            (float)$reservation['price'];

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
        |--------------------------------------------------------------------------
        | Stripe uses the smallest currency unit.
        |
        | Example:
        | $100.00 = 10000 cents
        |--------------------------------------------------------------------------
        */

        $amountInCents =
            (int)round($totalAmount * 100);


        /*
        |--------------------------------------------------------------------------
        | Create Stripe Checkout Session
        |--------------------------------------------------------------------------
        */

        try {

            $checkoutSession =
                $this->stripe
                    ->checkout
                    ->sessions
                    ->create([

                        'mode' => 'payment',

                        'line_items' => [

                            [
                                'price_data' => [

                                    'currency' => 'usd',

                                    'product_data' => [

                                        'name' =>
                                            'LuxeStay - ' .
                                            $reservation['room_name'],

                                        'description' =>
                                            $nights .
                                            ' night(s) hotel reservation'
                                    ],

                                    'unit_amount' =>
                                        $amountInCents
                                ],

                                'quantity' => 1
                            ]
                        ],


                        /*
                        |--------------------------------------------------------------------------
                        | Customer information
                        |--------------------------------------------------------------------------
                        */

                        'customer_email' =>
                            $reservation['customer_email'],


                        /*
                        |--------------------------------------------------------------------------
                        | Metadata
                        |--------------------------------------------------------------------------
                        |
                        | This lets our webhook identify exactly
                        | which LuxeStay reservation was paid.
                        |--------------------------------------------------------------------------
                        */

                        'metadata' => [

                            'reservation_id' =>
                                (string)$reservationId,

                            'user_id' =>
                                (string)$userId
                        ],


                        /*
                        |--------------------------------------------------------------------------
                        | Stripe → LuxeStay
                        |--------------------------------------------------------------------------
                        */

                        'success_url' =>
                            BASE_URL .
                            '/views/payments/stripe-success.php' .
                            '?session_id={CHECKOUT_SESSION_ID}',


                        /*
                        |--------------------------------------------------------------------------
                        | Customer cancels Stripe payment
                        |--------------------------------------------------------------------------
                        */

                        'cancel_url' =>
                            BASE_URL .
                            '/views/payments/index.php'
                    ]);


            return [

                'success' => true,

                'session_id' =>
                    $checkoutSession->id,

                'checkout_url' =>
                    $checkoutSession->url,

                'amount' =>
                    $totalAmount,

                'message' =>
                    'Stripe Checkout session created successfully.'
            ];


        } catch (Exception $e) {

            error_log(
                'Stripe Checkout Error: ' .
                $e->getMessage()
            );

            return [

                'success' => false,

                'message' =>
                    'Unable to start Stripe payment. Please try again.'
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Direct payment is disabled
    |--------------------------------------------------------------------------
    |
    | Payments must now go through Stripe Checkout.
    |
    */

    public function processPayment(
        int $reservationId,
        int $userId
    ): array {

        return [

            'success' => false,

            'message' =>
                'Direct payment is disabled. Please use Stripe Checkout.'
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | User payment history
    |--------------------------------------------------------------------------
    */

    public function userPayments(
        int $userId
    ): array {

        if ($userId <= 0) {
            return [];
        }

        return $this->paymentModel
            ->getUserPayments($userId);
    }
}
?>