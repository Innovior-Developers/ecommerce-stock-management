<?php

namespace App\Services\PaymentGateway;

use Stripe\StripeClient;
use Stripe\Webhook;
use App\Services\QuerySanitizer;
use Illuminate\Support\Facades\Log;

class StripeService implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('payment.stripe.secret'));
    }

    public function createPayment(array $data): array
    {
        try {
            $amount = (int) round($data['amount'] * 100); // Convert to cents
            $currency = QuerySanitizer::sanitize($data['currency']);
            $orderId = QuerySanitizer::sanitizeMongoId($data['order_id']);

            if (!$orderId) {
                return [
                    'success' => false,
                    'error' => 'Invalid order ID',
                ];
            }

            Log::info('Stripe Payment Creation', [
                'amount_dollars' => $data['amount'],
                'amount_cents' => $amount,
                'currency' => $currency,
            ]);

            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => strtolower($currency),
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $data['user_id'] ?? null,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('Stripe Payment Intent Created', [
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Payment Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function capturePayment(string $transactionId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->capture($transactionId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $transactionId,
                'amount' => (int) ($amount * 100),
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('payment.stripe.webhook_secret')
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPaymentStatus(string $transactionId): string
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);

            return match ($paymentIntent->status) {
                'succeeded' => 'completed',
                'processing' => 'processing',
                'canceled' => 'failed',
                default => 'pending',
            };
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
}
