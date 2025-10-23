<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateway\StripeService;
use App\Services\PaymentGateway\PayPalService;
use App\Services\PaymentGateway\PayHereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Stripe webhook handler
     * POST /api/webhooks/stripe
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            // ✅ Verify webhook signature
            $service = app(StripeService::class);

            if (!$service->verifyWebhook($payload, $signature)) {
                Log::warning('Stripe webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = json_decode($payload, true);

            Log::info('Stripe webhook received', [
                'event_type' => $event['type'],
                'event_id' => $event['id'],
            ]);

            // ✅ Handle different event types
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event['data']['object'], 'stripe');
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailure($event['data']['object'], 'stripe');
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event['data']['object'], 'stripe');
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * PayPal webhook handler
     * POST /api/webhooks/paypal
     */
    public function paypal(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Paypal-Transmission-Sig');

        try {
            // ✅ Verify webhook signature
            $service = app(PayPalService::class);

            if (!$service->verifyWebhook($payload, $signature)) {
                Log::warning('PayPal webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = json_decode($payload, true);

            Log::info('PayPal webhook received', [
                'event_type' => $event['event_type'],
                'event_id' => $event['id'],
            ]);

            // ✅ Handle different event types
            switch ($event['event_type']) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handlePaymentSuccess($event['resource'], 'paypal');
                    break;

                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.DECLINED':
                    $this->handlePaymentFailure($event['resource'], 'paypal');
                    break;

                case 'PAYMENT.CAPTURE.REFUNDED':
                    $this->handleRefund($event['resource'], 'paypal');
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('PayPal Webhook Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * PayHere webhook handler (notify_url)
     * POST /api/webhooks/payhere
     */
    public function payhere(Request $request)
    {
        $payload = $request->getContent();

        try {
            // ✅ Verify webhook signature
            $service = app(PayHereService::class);

            if (!$service->verifyWebhook($payload, '')) {
                Log::warning('PayHere webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = $request->all();
            $statusCode = $data['status_code'] ?? '';

            Log::info('PayHere webhook received', [
                'order_id' => $data['order_id'] ?? 'unknown',
                'status_code' => $statusCode,
            ]);

            // ✅ Handle payment status
            if ($statusCode == '2') { // Success
                $this->handlePaymentSuccess($data, 'payhere');
            } else {
                $this->handlePaymentFailure($data, 'payhere');
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('PayHere Webhook Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess(array $data, string $gateway)
    {
        try {
            // ✅ Extract transaction ID based on gateway
            $transactionId = match ($gateway) {
                'stripe' => $data['id'],
                'paypal' => $data['id'],
                'payhere' => $data['payment_id'] ?? $data['order_id'],
            };

            // ✅ Find payment
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::warning('Payment not found for successful transaction', [
                    'gateway' => $gateway,
                    'transaction_id' => $transactionId,
                ]);
                return;
            }

            // ✅ Prevent duplicate processing
            if ($payment->status === 'completed') {
                Log::info('Payment already marked as completed', [
                    'payment_id' => $payment->_id,
                ]);
                return;
            }

            // ✅ Update payment
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => $data,
            ]);

            // ✅ Create transaction record
            PaymentTransaction::create([
                'payment_id' => $payment->_id,
                'transaction_type' => 'capture',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => 'success',
                'gateway_transaction_id' => $transactionId,
                'gateway_response' => $data,
            ]);

            // ✅ Update order status
            $order = Order::where('_id', $payment->order_id)->first();
            if ($order && $order->status === 'pending') {
                $order->update(['status' => 'processing']);
            }

            Log::info('Payment marked as completed', [
                'payment_id' => $payment->_id,
                'order_id' => $payment->order_id,
                'gateway' => $gateway,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling payment success', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailure(array $data, string $gateway)
    {
        try {
            // ✅ Extract transaction ID
            $transactionId = match ($gateway) {
                'stripe' => $data['id'],
                'paypal' => $data['id'],
                'payhere' => $data['payment_id'] ?? $data['order_id'],
            };

            // ✅ Find payment
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::warning('Payment not found for failed transaction', [
                    'gateway' => $gateway,
                    'transaction_id' => $transactionId,
                ]);
                return;
            }

            // ✅ Update payment
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $data,
            ]);

            // ✅ Create transaction record
            PaymentTransaction::create([
                'payment_id' => $payment->_id,
                'transaction_type' => 'capture',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => 'failed',
                'gateway_transaction_id' => $transactionId,
                'gateway_response' => $data,
                'error_message' => $data['error_message'] ?? 'Payment failed',
            ]);

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->_id,
                'gateway' => $gateway,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling payment failure', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle refund
     */
    private function handleRefund(array $data, string $gateway)
    {
        try {
            $transactionId = match ($gateway) {
                'stripe' => $data['payment_intent'],
                'paypal' => $data['id'],
                'payhere' => null, // PayHere doesn't support automatic refunds
            };

            if (!$transactionId) {
                return;
            }

            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::warning('Payment not found for refund', [
                    'gateway' => $gateway,
                    'transaction_id' => $transactionId,
                ]);
                return;
            }

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->_id,
                'transaction_type' => 'refund',
                'amount' => $data['amount'] ?? $payment->amount,
                'currency' => $payment->currency,
                'status' => 'success',
                'gateway_transaction_id' => $data['id'],
                'gateway_response' => $data,
            ]);

            Log::info('Payment refunded', [
                'payment_id' => $payment->_id,
                'gateway' => $gateway,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling refund', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }
}