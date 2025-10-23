<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Services\PaymentGateway\StripeService;
use App\Services\PaymentGateway\PayPalService;
use App\Services\PaymentGateway\PayHereService;
use App\Services\QuerySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get appropriate payment service based on gateway
     */
    private function getPaymentService(string $gateway)
    {
        return match($gateway) {
            'stripe' => app(StripeService::class),
            'paypal' => app(PayPalService::class),
            'payhere' => app(PayHereService::class),
            default => throw new \Exception('Invalid payment gateway'),
        };
    }

    /**
     * Initialize payment
     * POST /api/payment/initiate
     */
    public function initiate(Request $request)
    {
        try {
            // ✅ Validate request
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string',
                'payment_method' => 'required|in:stripe,paypal,payhere',
                'currency' => 'required|in:USD,EUR,LKR',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $validated = $validator->validated();

            // ✅ Get authenticated user (set by JWTMiddleware)
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // ✅ Sanitize order ID
            $orderId = QuerySanitizer::sanitizeMongoId($validated['order_id']);
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            // ✅ Fetch order
            $order = Order::where('_id', $orderId)->first();
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // ✅ Verify user owns this order
            if ($user->isCustomer() && (!$user->customer || $order->customer_id !== $user->customer->_id)) {
                Log::warning('Payment initiation unauthorized', [
                    'user_id' => (string) $user->_id,
                    'order_id' => $orderId,
                    'order_customer_id' => (string) $order->customer_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to pay for this order',
                ], 403);
            }

            // ✅ Check if order already has a completed payment
            $existingPayment = Payment::where('order_id', $orderId)
                ->where('status', 'completed')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid',
                ], 400);
            }

            // ✅ Verify currency matches gateway
            $expectedGateway = config("payment.currency_gateway_map.{$validated['currency']}", 'stripe');
            if ($validated['payment_method'] !== $expectedGateway) {
                Log::warning('Currency gateway mismatch', [
                    'currency' => $validated['currency'],
                    'selected_gateway' => $validated['payment_method'],
                    'recommended_gateway' => $expectedGateway,
                ]);
            }

            // ✅ Create payment record
            $payment = Payment::create([
                'order_id' => $orderId,
                'user_id' => (string) $user->_id,
                'amount' => $order->total,
                'currency' => $validated['currency'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ✅ Initialize payment with gateway
            $service = $this->getPaymentService($validated['payment_method']);
            $result = $service->createPayment([
                'order_id' => $orderId,
                'user_id' => (string) $user->_id,
                'amount' => $order->total,
                'currency' => $validated['currency'],
                'first_name' => $user->customer->first_name ?? $user->name ?? 'Customer',
                'last_name' => $user->customer->last_name ?? '',
                'email' => $user->email,
                'phone' => $user->customer->phone ?? '',
                'address' => $order->shipping_address['street'] ?? '',
                'city' => $order->shipping_address['city'] ?? 'City',
                'items_description' => "Order #{$order->order_number}",
            ]);

            if ($result['success']) {
                // ✅ Update payment with gateway transaction ID
                $payment->update([
                    'gateway_transaction_id' => $result['transaction_id'],
                    'status' => 'processing',
                    'gateway_response' => $result,
                ]);

                Log::info('Payment initiated successfully', [
                    'payment_id' => (string) $payment->_id,
                    'order_id' => $orderId,
                    'gateway' => $validated['payment_method'],
                    'amount' => $order->total,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'data' => [
                        'payment_id' => (string) $payment->_id,
                        'transaction_id' => $result['transaction_id'],
                        'client_secret' => $result['client_secret'] ?? null, // Stripe
                        'approval_url' => $result['approval_url'] ?? null,   // PayPal
                        'payment_data' => $result['payment_data'] ?? null,   // PayHere
                        'action_url' => $result['action_url'] ?? null,       // PayHere
                    ],
                ]);
            }

            // ✅ Payment initiation failed
            $payment->update(['status' => 'failed']);

            Log::error('Payment initiation failed', [
                'payment_id' => (string) $payment->_id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Payment initialization failed',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Payment Initiation Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
            ], 500);
        }
    }

    /**
     * Confirm payment after gateway redirect
     * POST /api/payment/confirm
     */
    public function confirm(Request $request)
    {
        try {
            // ✅ Validate request
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|string',
                'transaction_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $validated = $validator->validated();

            // ✅ Get authenticated user
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // ✅ Sanitize payment ID
            $paymentId = QuerySanitizer::sanitizeMongoId($validated['payment_id']);
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment ID',
                ], 400);
            }

            // ✅ Fetch payment
            $payment = Payment::where('_id', $paymentId)->first();
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // ✅ Verify user owns this payment
            if ($payment->user_id !== (string) $user->_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // ✅ Check if payment already completed
            if ($payment->status === 'completed') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'data' => [
                        'payment_status' => 'completed',
                    ],
                ]);
            }

            // ✅ Get payment status from gateway
            $service = $this->getPaymentService($payment->payment_method);
            $status = $service->getPaymentStatus($validated['transaction_id']);

            // ✅ Update payment status
            $isCompleted = in_array($status, ['completed', 'succeeded']);
            $payment->update([
                'status' => $status,
                'paid_at' => $isCompleted ? now() : null,
            ]);

            // ✅ Update order status if payment completed
            if ($isCompleted) {
                $order = Order::where('_id', $payment->order_id)->first();
                if ($order) {
                    $order->update(['status' => 'processing']);

                    Log::info('Order status updated after payment', [
                        'order_id' => (string) $order->_id,
                        'payment_id' => (string) $payment->_id,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status confirmed',
                'data' => [
                    'payment_status' => $payment->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Confirmation Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed',
            ], 500);
        }
    }

    /**
     * Get payment status
     * GET /api/payment/status/{id}
     */
    public function status(Request $request, $id)
    {
        try {
            // ✅ Get authenticated user
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // ✅ Sanitize payment ID
            $paymentId = QuerySanitizer::sanitizeMongoId($id);
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment ID',
                ], 400);
            }

            // ✅ Fetch payment
            $payment = Payment::where('_id', $paymentId)
                ->with('order')
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // ✅ Verify user owns this payment
            if ($payment->user_id !== (string) $user->_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => (string) $payment->_id,
                    'order_id' => (string) $payment->order_id,
                    'order_number' => $payment->order->order_number ?? null,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Status Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status',
            ], 500);
        }
    }

    /**
     * List user's payments
     * GET /api/payment/history
     */
    public function history(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $perPage = min((int) $request->get('per_page', 10), 50);

            $payments = Payment::where('user_id', (string) $user->_id)
                ->with('order:_id,order_number,total,status')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'total_pages' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment History Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history',
            ], 500);
        }
    }
}