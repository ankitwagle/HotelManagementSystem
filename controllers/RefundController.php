<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/Payment.php';

class RefundController
{
    private \Stripe\StripeClient $stripe;
    private Payment $paymentModel;

    public function __construct()
    {
        $stripeConfig = require __DIR__ . '/../config/stripe.php';
        if (empty($stripeConfig['secret_key'])
            || strpos($stripeConfig['secret_key'], 'sk_test_') !== 0) {
            throw new Exception('Stripe test secret key is not configured correctly.');
        }

        $this->stripe = new \Stripe\StripeClient($stripeConfig['secret_key']);
        $this->paymentModel = new Payment();
    }

    public function refundReservation(int $reservationId): array
    {
        $payment = $this->paymentModel->getSuccessfulPaidPayment($reservationId);
        if (!$payment || strtolower(trim((string) $payment['status'])) === 'refunded') {
            if ($payment && strtolower(trim((string) $payment['status'])) === 'refunded') {
                return [
                    'success' => true,
                    'original_amount' => (float) $payment['amount'],
                    'service_charge' => (float) $payment['service_charge'],
                    'refund_amount' => (float) $payment['refund_amount'],
                    'refund_reference' => (string) $payment['refund_reference']
                ];
            }

            return [
                'success' => true,
                'no_refund_required' => true,
                'original_amount' => 0.0,
                'service_charge' => 0.0,
                'refund_amount' => 0.0,
                'refund_reference' => ''
            ];
        }

        if (strtolower(trim((string) $payment['payment_method'])) !== 'stripe') {
            return [
                'success' => false,
                'message' => 'This payment method cannot be refunded automatically.'
            ];
        }

        $amount = round((float) $payment['amount'], 2);
        $serviceCharge = round($amount * 0.10, 2);
        $refundAmount = round($amount - $serviceCharge, 2);
        $reference = trim((string) $payment['transaction_reference']);

        if ($amount <= 0 || $refundAmount <= 0 || $reference === '') {
            return [
                'success' => false,
                'message' => 'The stored payment is not eligible for a refund.'
            ];
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve(
                $reference,
                ['expand' => ['payment_intent']]
            );
            $paymentIntent = $session->payment_intent;
            $paymentIntentId = is_string($paymentIntent)
                ? $paymentIntent
                : (string) ($paymentIntent->id ?? '');

            if ($paymentIntentId === '') {
                return [
                    'success' => false,
                    'message' => 'Stripe did not return a PaymentIntent for this payment.'
                ];
            }

            $refund = $this->stripe->refunds->create(
                [
                    'payment_intent' => $paymentIntentId,
                    'amount' => (int) round($refundAmount * 100)
                ],
                [
                    'idempotency_key' => 'luxe_cancel_refund_' . $reservationId
                ]
            );

            $refundReference = (string) $refund->id;
            if ($refundReference === '') {
                return [
                    'success' => false,
                    'message' => 'Stripe did not return a refund reference.'
                ];
            }

            $marked = $this->paymentModel->markRefunded(
                (int) $payment['id'],
                $serviceCharge,
                $refundAmount,
                $refundReference,
                date('Y-m-d H:i:s')
            );

            if (!$marked) {
                $updatedPayment = $this->paymentModel
                    ->getSuccessfulPaidPayment($reservationId);
                if (!$updatedPayment
                    || strtolower(trim((string) $updatedPayment['status'])) !== 'refunded') {
                    return [
                        'success' => false,
                        'message' => 'Stripe refund succeeded, but the payment record could not be updated.'
                    ];
                }
            }

            return [
                'success' => true,
                'original_amount' => $amount,
                'service_charge' => $serviceCharge,
                'refund_amount' => $refundAmount,
                'refund_reference' => $refundReference
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Stripe refund failed. The reservation was not cancelled.'
            ];
        }
    }
}
