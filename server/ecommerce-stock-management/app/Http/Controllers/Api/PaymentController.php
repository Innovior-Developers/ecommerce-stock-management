<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Customer;
use App\Services\PaymentGateway\StripeService;
use App\Services\PaymentGateway\PayPalService;
use App\Services\PaymentGateway\PayHereService;
use App\Services\QuerySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private function getPaymentService(string $method)
    {
        return match ($method) {
            'stripe' => new StripeService(),
            'paypal' => new PayPalService(),
            'payhere' => new PayHereService(),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }

    /**
     * ✅ HELPER: Force convert to float (handles Decimal128, strings, numbers)
     */
    private function forceFloat($value): float
    {
        // Log what we receive
        Log::info('forceFloat called', [
            'value' => $value,
            'type' => gettype($value),
            'class' => is_object($value) ? get_class($value) : 'not_object',
        ]);

        // Handle Decimal128 objects
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $stringValue = $value->__toString();
                Log::info('Converted object to string', ['string_value' => $stringValue]);
                return (float) $stringValue;
            }
            // Try to cast object directly
            return (float) (string) $value;
        }

        // Handle strings
        if (is_string($value)) {
            return (float) $value;
        }

        // Handle numeric values
        return (float) $value;
    }

    /**
     * Initiate payment
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

            // ✅ Get authenticated user
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

            // ✅ DEBUG: Log raw order total
            Log::info('Raw order total', [
                'raw_total' => $order->total,
                'type' => gettype($order->total),
                'class' => is_object($order->total) ? get_class($order->total) : 'not_object',
            ]);

            // ✅ FIX: Force convert using helper
            $orderTotal = $this->forceFloat($order->total);

            Log::info('Payment initiation started', [
                'order_id' => $orderId,
                'raw_total' => $order->total,
                'converted_total' => $orderTotal,
                'total_type' => gettype($orderTotal),
                'currency' => $validated['currency'],
                'gateway' => $validated['payment_method'],
            ]);

            // ✅ Verify user owns this order (for customers only)
            if ($user->isCustomer()) {
                $customer = Customer::where('user_id', (string) $user->_id)->first();

                if (!$customer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer profile not found',
                    ], 404);
                }

                if ($order->customer_id !== (string) $customer->_id) {
                    Log::warning('Payment initiation unauthorized', [
                        'user_id' => (string) $user->_id,
                        'order_id' => $orderId,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to pay for this order',
                    ], 403);
                }
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

            // ✅ Get customer data for payment gateway
            $customerData = [
                'first_name' => $user->name ?? 'Customer',
                'last_name' => '',
                'email' => $user->email,
                'phone' => '',
            ];

            if ($user->isCustomer()) {
                $customer = Customer::where('user_id', (string) $user->_id)->first();
                if ($customer) {
                    $customerData = [
                        'first_name' => $customer->first_name ?? $user->name ?? 'Customer',
                        'last_name' => $customer->last_name ?? '',
                        'email' => $user->email,
                        'phone' => $customer->phone ?? '',
                    ];
                }
            }

            // ✅ MINIMAL FIX: Create payment and get ID immediately
            $payment = Payment::create([
                'order_id' => $orderId,
                'user_id' => (string) $user->_id,
                'amount' => $orderTotal,
                'currency' => $validated['currency'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'metadata' => [
                    'order_number' => $order->order_number,
                    'customer_name' => trim($customerData['first_name'] . ' ' . $customerData['last_name']),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ✅ FIX: Get the actual ID from database
            $paymentId = $payment->getKey();

            // ✅ FALLBACK: If still empty, query by order_id
            if (empty($paymentId)) {
                $payment = Payment::where('order_id', $orderId)
                    ->where('user_id', (string) $user->_id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($payment) {
                    $paymentId = (string) $payment->_id;
                }
            } else {
                $paymentId = (string) $paymentId;
            }

            Log::info('Payment record created', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
            ]);

            // ✅ DEBUG: Log what we're sending to gateway
            $gatewayData = [
                'order_id' => $orderId,
                'user_id' => (string) $user->_id,
                'amount' => $orderTotal,
                'currency' => $validated['currency'],
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'address' => $order->shipping_address['street'] ?? '',
                'city' => $order->shipping_address['city'] ?? 'City',
                'items_description' => "Order #{$order->order_number}",
            ];

            Log::info('Gateway data prepared', [
                'amount' => $gatewayData['amount'],
                'amount_type' => gettype($gatewayData['amount']),
                'gateway' => $validated['payment_method'],
            ]);

            // ✅ Initialize payment with gateway
            $service = $this->getPaymentService($validated['payment_method']);
            $result = $service->createPayment($gatewayData);

            if ($result['success']) {
                // ✅ Update payment with gateway transaction ID
                $payment->update([
                    'gateway_transaction_id' => $result['transaction_id'],
                    'status' => 'processing',
                    'gateway_response' => $result,
                ]);

                Log::info('Payment initiated successfully', [
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'gateway' => $validated['payment_method'],
                    'amount' => $orderTotal,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'data' => [
                        'payment_id' => $paymentId, // ✅ NOW POPULATED
                        'transaction_id' => $result['transaction_id'],
                        'client_secret' => $result['client_secret'] ?? null,
                        'approval_url' => $result['approval_url'] ?? null,
                        'payment_data' => $result['payment_data'] ?? null,
                        'action_url' => $result['action_url'] ?? null,
                    ],
                ]);
            }

            // ✅ Payment initiation failed
            $payment->update(['status' => 'failed']);

            Log::error('Payment initiation failed', [
                'payment_id' => $paymentId,
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
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $paymentId = QuerySanitizer::sanitizeMongoId($validated['payment_id']);
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment ID',
                ], 400);
            }

            $payment = Payment::where('_id', $paymentId)->first();
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            if ($payment->user_id !== (string) $user->_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($payment->status === 'completed') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'data' => [
                        'payment_status' => 'completed',
                    ],
                ]);
            }

            $service = $this->getPaymentService($payment->payment_method);
            $status = $service->getPaymentStatus($validated['transaction_id']);

            $isCompleted = in_array($status, ['completed', 'succeeded']);
            $payment->update([
                'status' => $status,
                'paid_at' => $isCompleted ? now() : null,
            ]);

            if ($isCompleted) {
                $order = Order::where('_id', $payment->order_id)->first();
                if ($order) {
                    $order->update(['status' => 'processing']);
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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $paymentId = QuerySanitizer::sanitizeMongoId($id);
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment ID',
                ], 400);
            }

            $payment = Payment::where('_id', $paymentId)
                ->with('order')
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

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
