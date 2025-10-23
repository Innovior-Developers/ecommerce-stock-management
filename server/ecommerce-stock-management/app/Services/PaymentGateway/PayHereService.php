<?php

namespace App\Services\PaymentGateway;

use App\Services\QuerySanitizer;
use Illuminate\Support\Facades\Log;

class PayHereService implements PaymentGatewayInterface
{
    private string $merchantId;
    private string $merchantSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('payment.payhere.merchant_id');
        $this->merchantSecret = config('payment.payhere.merchant_secret');
        $this->baseUrl = config('payment.payhere.mode') === 'sandbox'
            ? 'https://sandbox.payhere.lk'
            : 'https://www.payhere.lk';
    }

    public function createPayment(array $data): array
    {
        try {
            $orderId = QuerySanitizer::sanitizeMongoId($data['order_id']);
            $amount = number_format($data['amount'], 2, '.', '');

            if (!$orderId) {
                return [
                    'success' => false,
                    'error' => 'Invalid order ID',
                ];
            }

            // ✅ Generate PayHere hash
            $hash = strtoupper(
                md5(
                    $this->merchantId .
                        $orderId .
                        $amount .
                        'LKR' .
                        strtoupper(md5($this->merchantSecret))
                )
            );

            $paymentData = [
                'merchant_id' => $this->merchantId,
                'return_url' => config('payment.payhere.return_url'),
                'cancel_url' => config('payment.payhere.cancel_url'),
                'notify_url' => config('payment.payhere.notify_url'),
                'order_id' => $orderId,
                'items' => $data['items_description'] ?? "Order Payment",
                'currency' => 'LKR',
                'amount' => $amount,
                'first_name' => QuerySanitizer::sanitize($data['first_name'] ?? 'Customer'),
                'last_name' => QuerySanitizer::sanitize($data['last_name'] ?? ''),
                'email' => QuerySanitizer::sanitize($data['email'] ?? ''),
                'phone' => QuerySanitizer::sanitize($data['phone'] ?? ''),
                'address' => QuerySanitizer::sanitize($data['address'] ?? ''),
                'city' => QuerySanitizer::sanitize($data['city'] ?? 'Colombo'),
                'country' => 'Sri Lanka',
                'hash' => $hash,
            ];

            Log::info('PayHere Payment Created', [
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'transaction_id' => $orderId, // PayHere uses order_id as transaction reference
                'payment_data' => $paymentData,
                'action_url' => $this->baseUrl . '/pay/checkout',
            ];
        } catch (\Exception $e) {
            Log::error('PayHere Payment Error', [
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
        // PayHere doesn't require manual capture
        return [
            'success' => true,
            'status' => 'completed',
            'message' => 'PayHere payments are auto-captured',
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        // PayHere refunds are manual through merchant dashboard
        Log::warning('PayHere Manual Refund Required', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return [
            'success' => false,
            'error' => 'PayHere refunds must be processed manually through the merchant dashboard',
            'message' => 'Please login to PayHere merchant dashboard to process this refund',
        ];
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        // PayHere uses MD5 hash verification
        parse_str($payload, $data);

        $merchantId = $data['merchant_id'] ?? '';
        $orderId = $data['order_id'] ?? '';
        $amount = $data['payhere_amount'] ?? '';
        $currency = $data['payhere_currency'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $receivedHash = $data['md5sig'] ?? '';

        // Generate local hash
        $localHash = strtoupper(
            md5(
                $merchantId .
                    $orderId .
                    $amount .
                    $currency .
                    $statusCode .
                    strtoupper(md5($this->merchantSecret))
            )
        );

        $isValid = hash_equals($localHash, $receivedHash);

        Log::info('PayHere Webhook Verification', [
            'valid' => $isValid,
            'order_id' => $orderId,
            'status_code' => $statusCode,
        ]);

        return $isValid;
    }

    public function getPaymentStatus(string $transactionId): string
    {
        // PayHere doesn't provide API for checking status
        // Status is received via webhook only
        Log::warning('PayHere Status Check Not Supported', [
            'transaction_id' => $transactionId,
        ]);

        return 'unknown';
    }
}
