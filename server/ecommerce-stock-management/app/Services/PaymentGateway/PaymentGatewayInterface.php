<?php

namespace App\Services\PaymentGateway;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent/session
     *
     * @param array $data Payment data (amount, currency, order_id, user_id, etc.)
     * @return array Returns [
     *   'success' => bool,
     *   'transaction_id' => string,
     *   'client_secret' => string (Stripe only),
     *   'approval_url' => string (PayPal/PayHere),
     *   'payment_data' => array (PayHere),
     *   'action_url' => string (PayHere),
     *   'status' => string,
     *   'error' => string (if failed)
     * ]
     */
    public function createPayment(array $data): array;

    /**
     * Capture/complete a payment
     *
     * @param string $transactionId Gateway transaction ID
     * @return array Returns [
     *   'success' => bool,
     *   'status' => string,
     *   'amount' => float,
     *   'error' => string (if failed)
     * ]
     */
    public function capturePayment(string $transactionId): array;

    /**
     * Refund a payment
     *
     * @param string $transactionId Gateway transaction ID
     * @param float $amount Amount to refund
     * @return array Returns [
     *   'success' => bool,
     *   'refund_id' => string,
     *   'status' => string,
     *   'error' => string (if failed)
     * ]
     */
    public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature
     * @return bool True if signature is valid
     */
    public function verifyWebhook(string $payload, string $signature): bool;

    /**
     * Get payment status from gateway
     *
     * @param string $transactionId Gateway transaction ID
     * @return string Status (pending, processing, completed, failed)
     */
    public function getPaymentStatus(string $transactionId): string;
}