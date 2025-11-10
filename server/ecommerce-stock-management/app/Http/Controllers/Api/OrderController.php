<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\QuerySanitizer;

class OrderController extends Controller
{
    /**
     * Admin views all orders
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['customer.user']);

            // Sanitize status filter
            if ($request->has('status')) {
                $status = QuerySanitizer::sanitize($request->get('status'));
                $query->where('status', $status);
            }

            // Sanitize customer_id filter
            if ($request->has('customer_id')) {
                $customerId = QuerySanitizer::sanitizeMongoId($request->get('customer_id'));
                if ($customerId) {
                    $query->where('customer_id', $customerId);
                }
            }

            // Date filters
            if ($request->has('date_from')) {
                $dateFrom = QuerySanitizer::sanitize($request->get('date_from'));
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($request->has('date_to')) {
                $dateTo = QuerySanitizer::sanitize($request->get('date_to'));
                $query->where('created_at', '<=', $dateTo);
            }

            $orders = $query->latest()->get();

            $ordersData = $orders->map(function ($order) {
                $payment = Payment::where('order_id', (string) $order->_id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                return [
                    '_id' => (string) $order->_id,
                    'order_number' => $order->order_number,
                    'customer' => [
                        '_id' => (string) ($order->customer->_id ?? ''),
                        'name' => ($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''),
                        'email' => $order->customer->user->email ?? '',
                    ],
                    'items' => $order->items,
                    'shipping_address' => $order->shipping_address,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_status' => $payment?->status ?? 'unpaid',
                    'payment_method' => $payment?->payment_method,
                    'paid_at' => $payment?->paid_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders'
            ], 500);
        }
    }

    /**
     * Customer views their own orders
     */
    public function myOrders(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $customer = Customer::where('user_id', (string) $user->_id)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer profile not found',
                ], 404);
            }

            $query = Order::where('customer_id', (string) $customer->_id);

            if ($request->has('status')) {
                $status = QuerySanitizer::sanitize($request->get('status'));
                if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
                    $query->where('status', $status);
                }
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            $ordersData = $orders->map(function ($order) {
                $payment = Payment::where('order_id', (string) $order->_id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                return [
                    '_id' => (string) $order->_id,
                    'order_number' => $order->order_number,
                    'items' => $order->items,
                    'shipping_address' => $order->shipping_address,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_status' => $payment?->status ?? 'unpaid',
                    'payment_method' => $payment?->payment_method,
                    'paid_at' => $payment?->paid_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Customer creates order with proper _id retrieval
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== ORDER CREATION REQUEST START ===');
            Log::info('Request data:', $request->all());

            $user = $request->user();

            if (!$user) {
                Log::error('User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            Log::info('Authenticated user:', [
                'user_id' => (string) $user->_id,
                'user_email' => $user->email,
            ]);

            $customer = Customer::where('user_id', (string) $user->_id)->first();

            if (!$customer) {
                Log::error('Customer profile not found', [
                    'user_id' => (string) $user->_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Customer profile not found. Please complete your profile first.',
                ], 404);
            }

            Log::info('Customer found:', [
                'customer_id' => (string) $customer->_id,
                'customer_name' => $customer->first_name . ' ' . $customer->last_name,
            ]);

            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string',
                'items.*.product_name' => 'nullable|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'shipping_address' => 'required|array',
                'shipping_address.street' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.country' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'payment' => 'required|array',
                'payment.method' => 'required|in:stripe,paypal,payhere',
                'notes' => 'nullable|string',
            ]);

            Log::info('Validation passed');

            foreach ($validated['items'] as $key => $item) {
                $productId = QuerySanitizer::sanitizeMongoId($item['product_id']);
                if (!$productId) {
                    Log::error('Invalid product ID', [
                        'index' => $key,
                        'product_id' => $item['product_id'],
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => "Invalid product ID in item {$key}",
                    ], 400);
                }

                $product = Product::where('_id', $productId)->first();

                if (!$product) {
                    Log::error('Product not found', [
                        'index' => $key,
                        'product_id' => $productId,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => "Product not found for item {$key}",
                    ], 404);
                }

                if (empty($item['product_name'])) {
                    $validated['items'][$key]['product_name'] = $product->name;
                }

                if ($product->stock_quantity < $item['quantity']) {
                    Log::error('Insufficient stock', [
                        'product_id' => $productId,
                        'requested' => $item['quantity'],
                        'available' => $product->stock_quantity,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}",
                    ], 400);
                }

                $validated['items'][$key]['product_id'] = $productId;
                $validated['items'][$key]['subtotal'] = $item['quantity'] * $item['unit_price'];
            }

            Log::info('Product IDs sanitized and verified');

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = round($subtotal * 0.08, 2);
            $shipping_cost = $subtotal > 100 ? 0 : 15.99;
            $total = round($subtotal + $tax + $shipping_cost, 2);

            Log::info('Totals calculated:', [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping_cost,
                'total' => $total,
            ]);

            $customerId = (string) $customer->_id;

            $orderData = [
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'customer_id' => $customerId,
                'items' => $validated['items'],
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['shipping_address'],
                'payment' => array_merge($validated['payment'], ['status' => 'pending']),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping_cost,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ];

            Log::info('Order data prepared:', $orderData);

            // ✅ FIX: Create order and immediately retrieve fresh copy
            $order = Order::create($orderData);

            // ✅ FIX: Query the order again by order_number to ensure _id is populated
            $order = Order::where('order_number', $order->order_number)->first();

            if (!$order || !$order->_id) {
                Log::error('Order creation failed - _id not populated', [
                    'order_number' => $orderData['order_number'],
                ]);
                throw new \Exception('Order creation failed: ID not generated');
            }

            $orderId = (string) $order->_id;

            Log::info('Order created successfully:', [
                'order_id' => $orderId,
                'order_number' => $order->order_number,
            ]);

            Cache::flush();

            Log::info('=== ORDER CREATION REQUEST END ===');

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    '_id' => $orderId,
                    'order_number' => $order->order_number,
                    'total' => (string) $order->total,
                    'status' => $order->status,
                    'items_count' => count($order->items),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order creation exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);
            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::with('customer.user')->find($sanitizedId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $payment = Payment::where('order_id', $sanitizedId)
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    '_id' => (string) $order->_id,
                    'order_number' => $order->order_number,
                    'customer' => [
                        'name' => ($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''),
                        'email' => $order->customer->user->email ?? '',
                    ],
                    'items' => $order->items,
                    'shipping_address' => $order->shipping_address,
                    'billing_address' => $order->billing_address,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_status' => $payment?->status ?? 'unpaid',
                    'payment_method' => $payment?->payment_method,
                    'paid_at' => $payment?->paid_at,
                    'tracking_number' => $order->tracking_number,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);
            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::find($sanitizedId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
                'tracking_number' => 'sometimes|nullable|string',
                'notes' => 'sometimes|nullable|string',
            ]);

            $order->update($validated);

            Log::info('Order updated', ['order_id' => $sanitizedId]);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => [
                    '_id' => (string) $order->_id,
                    'status' => $order->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Order update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);
            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::find($sanitizedId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $order->delete();

            Log::info('Order deleted', ['order_id' => $sanitizedId]);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Order deletion failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
            ], 500);
        }
    }
}
