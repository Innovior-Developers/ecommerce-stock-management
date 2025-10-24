<?php
// filepath: c:\Users\CHAMA COMPUTERS\Downloads\Innovior IOT\esm\server\ecommerce-stock-management\app\Http\Controllers\Api\WebhookController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Services\PaymentGateway\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle PayPal webhook notifications
     * POST /api/webhooks/paypal
     */
    public function paypal(Request $request)
    {
        try {
            // ✅ Log raw webhook data
            Log::info('PayPal Webhook Received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);

            // ✅ Get webhook data
            $webhookData = $request->all();

            // ✅ Verify webhook signature (optional but recommended)
            // $service = new PayPalService();
            // if (!$service->verifyWebhook($request->getContent(), $request->header('PAYPAL-TRANSMISSION-SIG'))) {
            //     Log::error('PayPal Webhook Signature Verification Failed');
            //     return response()->json(['error' => 'Invalid signature'], 400);
            // }

            // ✅ Extract event type and resource
            $eventType = $webhookData['event_type'] ?? null;
            $resource = $webhookData['resource'] ?? [];

            Log::info('PayPal Webhook Event', [
                'event_type' => $eventType,
                'resource_id' => $resource['id'] ?? 'unknown',
            ]);

            // ✅ Handle payment completion
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'CHECKOUT.ORDER.APPROVED') {
                // PayPal sends different IDs depending on event type
                $transactionId = $resource['id'] ??
                    $resource['supplementary_data']['related_ids']['order_id'] ??
                    null;

                if (!$transactionId) {
                    Log::error('PayPal Webhook: Transaction ID missing', [
                        'event_type' => $eventType,
                        'resource' => $resource,
                    ]);
                    return response()->json(['error' => 'Transaction ID missing'], 400);
                }

                // ✅ Find payment by PayPal transaction ID
                $payment = Payment::where('gateway_transaction_id', $transactionId)
                    ->orWhere(function ($query) use ($transactionId) {
                        $query->where('gateway_response.id', $transactionId);
                    })
                    ->first();

                if (!$payment) {
                    Log::warning('PayPal Webhook: Payment not found', [
                        'transaction_id' => $transactionId,
                    ]);
                    // Return 200 to prevent PayPal retries
                    return response()->json(['message' => 'Payment not found'], 200);
                }

                // ✅ Check if already processed
                if ($payment->status === 'completed') {
                    Log::info('PayPal Webhook: Payment already completed', [
                        'payment_id' => (string) $payment->_id,
                    ]);
                    return response()->json(['message' => 'Already processed'], 200);
                }

                // ✅ Update payment status
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['webhook_event' => $webhookData]
                    ),
                ]);

                // ✅ Update order status
                $order = Order::where('_id', $payment->order_id)->first();
                if ($order) {
                    $order->update([
                        'payment_status' => 'completed',
                        'status' => 'processing',
                        'paid_at' => now(),
                    ]);

                    Log::info('PayPal Payment Completed', [
                        'payment_id' => (string) $payment->_id,
                        'order_id' => (string) $order->_id,
                        'transaction_id' => $transactionId,
                    ]);
                } else {
                    Log::error('PayPal Webhook: Order not found', [
                        'order_id' => $payment->order_id,
                    ]);
                }

                return response()->json(['message' => 'Webhook processed'], 200);
            }

            // ✅ Handle payment denial/failure
            if ($eventType === 'PAYMENT.CAPTURE.DENIED') {
                $transactionId = $resource['id'] ?? null;

                $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => array_merge(
                            $payment->gateway_response ?? [],
                            ['webhook_event' => $webhookData]
                        ),
                    ]);

                    $order = Order::where('_id', $payment->order_id)->first();
                    if ($order) {
                        $order->update(['payment_status' => 'failed']);
                    }

                    Log::info('PayPal Payment Denied', [
                        'payment_id' => (string) $payment->_id,
                        'transaction_id' => $transactionId,
                    ]);
                }

                return response()->json(['message' => 'Webhook processed'], 200);
            }

            // ✅ Log unhandled event types
            Log::info('PayPal Webhook: Unhandled event type', [
                'event_type' => $eventType,
            ]);

            return response()->json(['message' => 'Event type not handled'], 200);
        } catch (\Exception $e) {
            Log::error('PayPal Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ Return 200 to prevent PayPal retries on our application errors
            return response()->json(['error' => 'Internal error'], 200);
        }
    }

    /**
     * Handle Stripe webhook notifications
     * POST /api/webhooks/stripe
     */
    public function stripe(Request $request)
    {
        // Stripe webhook handler (already implemented via Stripe CLI)
        // Keep existing implementation
    }

    /**
     * Handle PayHere webhook notifications
     * POST /api/webhooks/payhere
     */
    public function payhere(Request $request)
    {
        // PayHere webhook handler (keep existing)
    }
}
