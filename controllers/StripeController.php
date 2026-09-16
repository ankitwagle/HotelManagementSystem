<?php

require_once __DIR__ . '/../vendor/autoload.php';

class StripeController
{
    private \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $stripeConfig = require __DIR__ . '/../config/stripe.php';

        if (
            empty($stripeConfig['secret_key']) ||
            strpos($stripeConfig['secret_key'], 'sk_test_') !== 0
        ) {
            throw new Exception(
                'Stripe test secret key is not configured correctly.'
            );
        }

        $this->stripe = new \Stripe\StripeClient(
            $stripeConfig['secret_key']
        );
    }

    /**
     * Create a Stripe Checkout Session.
     *
     * The reservation is NOT marked as paid here.
     * Payment will be confirmed by the Stripe webhook.
     */
    public function createCheckoutSession(
        array $reservation,
        int $userId
    ): array {

        try {

            $reservationId =
                (int)($reservation['reservation_id'] ?? 0);

            $pricePerNight =
                (float)($reservation['price'] ?? 0);

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

            if ($pricePerNight <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid payment amount.'
                ];
            }

            /*
             * Calculate number of nights.
             */
            $checkIn = new DateTime(
                $reservation['check_in']
            );

            $checkOut = new DateTime(
                $reservation['check_out']
            );

            $nights =
                $checkIn->diff($checkOut)->days;

            if ($nights < 1) {
                $nights = 1;
            }

            /*
             * Calculate total amount.
             *
             * Stripe expects the amount in the
             * smallest currency unit.
             *
             * Example:
             * $150.00 = 15000 cents
             */
            $totalAmount =
                $nights * $pricePerNight;

            $stripeAmount =
                (int)round($totalAmount * 100);

            if ($stripeAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid Stripe amount.'
                ];
            }

            /*
             * Create Stripe Checkout Session.
             */
            $session =
                $this->stripe->checkout->sessions->create([
                    'mode' => 'payment',

                    'payment_method_types' => [
                        'card'
                    ],

                    'line_items' => [
                        [
                            'price_data' => [
                                'currency' => 'usd',

                                'product_data' => [
                                    'name' =>
                                        'LuxeStay - ' .
                                        ($reservation['room_name']
                                            ?? 'Hotel Room'),

                                    'description' =>
                                        'Reservation #' .
                                        $reservationId .
                                        ' - ' .
                                        $nights .
                                        ' night(s)'
                                ],

                                'unit_amount' =>
                                    $stripeAmount
                            ],

                            'quantity' => 1
                        ]
                    ],

                    'customer_email' =>
                        $reservation['customer_email']
                        ?? null,

                    'metadata' => [
                        'reservation_id' =>
                            (string)$reservationId,

                        'user_id' =>
                            (string)$userId
                    ],

                    /*
                     * IMPORTANT:
                     *
                     * This page is only a return page.
                     * It must NOT mark the payment as paid.
                     */
                    'success_url' =>
                        $this->getBaseUrl() .
                        '/views/payments/success.php' .
                        '?session_id={CHECKOUT_SESSION_ID}',

                    'cancel_url' =>
                        $this->getBaseUrl() .
                        '/views/payments/index.php'
                ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'amount' => $totalAmount
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {

            return [
                'success' => false,
                'message' =>
                    'Stripe payment could not be started.'
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' =>
                    'Unable to start payment.'
            ];
        }
    }

    /**
     * Get the application base URL.
     *
     * For local XAMPP/Laragon development this normally
     * resolves to http://localhost.
     */
    private function getBaseUrl(): string
    {
        $https =
            (!empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off');

        $protocol =
            $https ? 'https' : 'http';

        $host =
            $_SERVER['HTTP_HOST']
            ?? 'localhost';

        return $protocol . '://' . $host;
    }
}
?>