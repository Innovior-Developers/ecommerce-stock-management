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
            // ✅ Sanitize inputs
            $amount = (int) ($data['amount'] * 100); // Convert to cents
            $currency = QuerySanitizer::sanitize($data['currency']);
            $orderId = QuerySanitizer::sanitizeMongoId($data['order_id']);

            if (!$orderId) {
                return [
                    'success' => false,
                    'error' => 'Invalid order ID',
                ];
            }

            // ✅ Create payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => strtolower($currency),
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $data['user_id'] ?? null,
                    'integration_check' => 'accept_a_payment',
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('Stripe Payment Intent Created', [
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $orderId,
                'amount' => $amount,
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
                'code' => $e->getStripeCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Payment General Error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment initialization failed',
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
                'currency' => $paymentIntent->currency,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Capture Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

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

            Log::info('Stripe Refund Created', [
                'refund_id' => $refund->id,
                'payment_intent' => $transactionId,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Refund Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

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

            Log::info('Stripe Webhook Verified', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            return true;
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Invalid Payload', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook Invalid Signature', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getPaymentStatus(string $transactionId): string
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);

            // Map Stripe statuses to our internal statuses
            return match ($paymentIntent->status) {
                'succeeded' => 'completed',
                'processing' => 'processing',
                'requires_payment_method' => 'pending',
                'requires_confirmation' => 'pending',
                'requires_action' => 'pending',
                'canceled' => 'failed',
                default => 'pending',
            };
        } catch (\Exception $e) {
            Log::error('Stripe Status Check Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return 'unknown';
        }
    }
}