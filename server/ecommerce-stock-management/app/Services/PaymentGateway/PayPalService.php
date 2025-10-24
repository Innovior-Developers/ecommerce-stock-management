<?php

namespace App\Services\PaymentGateway;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Services\QuerySanitizer;
use Illuminate\Support\Facades\Log;

class PayPalService implements PaymentGatewayInterface
{
    private PayPalClient $paypal;

    public function __construct()
    {
        $this->paypal = new PayPalClient;

        // ✅ FIX: Set API credentials from config
        $config = config('payment.paypal');

        Log::info('PayPal Configuration Loaded', [
            'mode' => $config['mode'] ?? 'unknown',
            'has_client_id' => !empty($config['sandbox']['client_id']),
            'has_client_secret' => !empty($config['sandbox']['client_secret']),
            'payment_action' => $config['payment_action'] ?? 'missing',
        ]);

        $this->paypal->setApiCredentials($config);

        // ✅ Get access token
        $token = $this->paypal->getAccessToken();

        if (isset($token['error'])) {
            Log::error('PayPal Access Token Error', [
                'error' => $token['error'],
                'message' => $token['error_description'] ?? 'Unknown error',
            ]);

            throw new \Exception('PayPal authentication failed: ' . ($token['error_description'] ?? 'Unknown error'));
        }

        Log::info('PayPal Access Token Retrieved Successfully');
    }

    public function createPayment(array $data): array
    {
        try {
            $orderId = QuerySanitizer::sanitizeMongoId($data['order_id']);

            // ✅ SIMPLIFIED: Direct float usage
            $amount = number_format((float) $data['amount'], 2, '.', '');

            if (!$orderId) {
                return [
                    'success' => false,
                    'error' => 'Invalid order ID',
                ];
            }

            Log::info('PayPal Payment Creation', [
                'amount' => $amount,
                'currency' => $data['currency'],
                'order_id' => $orderId,
            ]);

            $order = $this->paypal->createOrder([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $orderId,
                    'amount' => [
                        'currency_code' => strtoupper($data['currency']),
                        'value' => $amount,
                    ],
                    'description' => $data['items_description'] ?? "Order #{$orderId}",
                ]],
                'application_context' => [
                    'return_url' => config('payment.paypal.return_url'),
                    'cancel_url' => config('payment.paypal.cancel_url'),
                    'brand_name' => config('app.name'),
                    'user_action' => 'PAY_NOW',
                ],
            ]);

            if (isset($order['error'])) {
                Log::error('PayPal Order Creation Error', [
                    'error' => $order['error'],
                    'message' => $order['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $order['message'] ?? 'PayPal order creation failed',
                ];
            }

            // Find approval URL
            $approvalUrl = null;
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            Log::info('PayPal Order Created', [
                'order_id' => $order['id'],
                'approval_url' => $approvalUrl,
            ]);

            return [
                'success' => true,
                'transaction_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'status' => 'pending',
            ];
        } catch (\Exception $e) {
            Log::error('PayPal Payment Error', [
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
            $result = $this->paypal->capturePaymentOrder($transactionId);

            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Capture failed',
                ];
            }

            $captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            Log::info('PayPal Payment Captured', [
                'order_id' => $transactionId,
                'capture_id' => $captureId,
                'status' => $result['status'],
            ]);

            return [
                'success' => true,
                'status' => strtolower($result['status']),
                'capture_id' => $captureId,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal Capture Error', [
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
            $refund = $this->paypal->refundCapturedPayment(
                $transactionId,
                'Customer Refund Request',
                number_format($amount, 2, '.', ''),
                'USD'
            );

            if (isset($refund['error'])) {
                return [
                    'success' => false,
                    'error' => $refund['message'] ?? 'Refund failed',
                ];
            }

            Log::info('PayPal Refund Created', [
                'refund_id' => $refund['id'],
                'status' => $refund['status'],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'status' => strtolower($refund['status']),
            ];
        } catch (\Exception $e) {
            Log::error('PayPal Refund Error', [
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
            $headers = getallheaders();

            $result = $this->paypal->verifyWebHook([
                'auth_algo' => $headers['Paypal-Auth-Algo'] ?? '',
                'cert_url' => $headers['Paypal-Cert-Url'] ?? '',
                'transmission_id' => $headers['Paypal-Transmission-Id'] ?? '',
                'transmission_sig' => $signature,
                'transmission_time' => $headers['Paypal-Transmission-Time'] ?? '',
                'webhook_id' => config('payment.paypal.webhook_id'),
                'webhook_event' => json_decode($payload, true),
            ]);

            $isValid = isset($result['verification_status']) &&
                $result['verification_status'] === 'SUCCESS';

            Log::info('PayPal Webhook Verification', [
                'valid' => $isValid,
                'status' => $result['verification_status'] ?? 'unknown',
            ]);

            return $isValid;
        } catch (\Exception $e) {
            Log::error('PayPal Webhook Verification Error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getPaymentStatus(string $transactionId): string
    {
        try {
            $order = $this->paypal->showOrderDetails($transactionId);

            if (isset($order['error'])) {
                return 'unknown';
            }

            return match (strtoupper($order['status'])) {
                'COMPLETED' => 'completed',
                'APPROVED' => 'processing',
                'CREATED' => 'pending',
                'SAVED' => 'pending',
                'VOIDED', 'CANCELLED' => 'failed',
                default => 'pending',
            };
        } catch (\Exception $e) {
            Log::error('PayPal Status Check Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return 'unknown';
        }
    }
}
