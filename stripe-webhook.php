<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Payment.php';

$stripeConfig = require __DIR__ . '/config/stripe.php';

$payload = @file_get_contents('php://input');

$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$webhookSecret =
    $stripeConfig['webhook_secret'] ?? '';

if ($payload === false || $payload === '') {
    http_response_code(400);
    exit('Empty webhook payload.');
}

if ($webhookSecret === '') {
    http_response_code(500);
    exit('Stripe webhook secret is not configured.');
}

try {

    /*
     * Verify that the webhook really came from Stripe.
     */
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sigHeader,
        $webhookSecret
    );

} catch (\UnexpectedValueException $e) {

    http_response_code(400);
    exit('Invalid webhook payload.');

} catch (\Stripe\Exception\SignatureVerificationException $e) {

    http_response_code(400);
    exit('Invalid webhook signature.');

} catch (Exception $e) {

    http_response_code(400);
    exit('Webhook verification failed.');
}


/*
|--------------------------------------------------------------------------
| HANDLE STRIPE EVENTS
|--------------------------------------------------------------------------
*/

if ($event->type === 'checkout.session.completed') {

    $session = $event->data->object;

    /*
     * Make sure Stripe actually reports
     * the Checkout Session as paid.
     */
    if (($session->payment_status ?? '') !== 'paid') {
        http_response_code(200);
        exit('Payment not completed.');
    }

    /*
     * Get reservation/user information
     * that we stored in Stripe metadata.
     */
    $reservationId = (int)(
        $session->metadata->reservation_id ?? 0
    );

    $userId = (int)(
        $session->metadata->user_id ?? 0
    );

    if ($reservationId <= 0 || $userId <= 0) {
        http_response_code(400);
        exit('Missing payment metadata.');
    }

    /*
     * Stripe Checkout Session ID.
     *
     * We store this as the transaction reference.
     */
    $transactionReference =
        (string)$session->id;

    /*
     * Stripe amount is stored in cents.
     */
    $amount =
        ((int)($session->amount_total ?? 0)) / 100;

    if ($amount <= 0) {
        http_response_code(400);
        exit('Invalid payment amount.');
    }

    try {

        $paymentModel = new Payment();

        /*
         * Idempotency protection:
         *
         * Stripe can send the same webhook more than once.
         *
         * If this payment was already recorded,
         * do not create another payment row.
         */
        $existingPayment =
            $paymentModel->getPaymentByReservation(
                $reservationId
            );

        if ($existingPayment) {

            $existingReference =
                (string)(
                    $existingPayment['transaction_reference']
                    ?? ''
                );

            if (
                $existingReference ===
                $transactionReference
            ) {
                http_response_code(200);
                exit('Webhook already processed.');
            }

            $existingStatus =
                strtolower(
                    trim(
                        (string)(
                            $existingPayment['status']
                            ?? ''
                        )
                    )
                );

            if ($existingStatus === 'paid') {
                http_response_code(200);
                exit('Reservation already paid.');
            }
        }

        /*
         * Create the payment record only after
         * Stripe's signed webhook confirms payment.
         */
        $paymentId =
            $paymentModel->createPayment(
                $reservationId,
                $amount,
                'Stripe',
                $transactionReference
            );

        http_response_code(200);

        echo 'Payment recorded successfully.';

        exit;

    } catch (PDOException $e) {

        /*
         * Return an error so Stripe can retry
         * the webhook.
         */
        http_response_code(500);

        exit('Database error.');
    }
}


/*
|--------------------------------------------------------------------------
| OTHER STRIPE EVENTS
|--------------------------------------------------------------------------
|
| We don't need to process them yet.
|
*/

http_response_code(200);

echo 'Event received.';