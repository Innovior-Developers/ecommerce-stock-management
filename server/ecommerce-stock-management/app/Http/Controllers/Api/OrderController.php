<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\QuerySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * List orders (customers see only their orders, admins see all)
     * GET /api/customer/orders or /api/admin/orders
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $query = Order::with(['customer.user:_id,name,email']);

            // ✅ If customer, only show their orders
            if ($user->isCustomer() && $user->customer) {
                $query->where('customer_id', (string) $user->customer->_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $status = QuerySanitizer::sanitize($request->status);
                $query->where('status', $status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $paymentStatus = QuerySanitizer::sanitize($request->payment_status);
                $query->where('payment_status', $paymentStatus);
            }

            $perPage = min((int) $request->get('per_page', 20), 50);
            $orders = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'total_pages' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Order Index Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
            ], 500);
        }
    }

    /**
     * Create new order (customer route only)
     * POST /api/customer/orders
     */
    public function store(Request $request)
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

            // ✅ Validate request (NO customer_id required!)
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'shipping_address' => 'required|array',
                'shipping_address.street' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.country' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'billing_address' => 'sometimes|array',
                'payment.method' => 'required|string|in:stripe,paypal,payhere,cod',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ✅ Auto-extract customer_id from JWT token
            if (!$user->customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer profile not found. Please complete your profile first.',
                ], 400);
            }

            $customerId = (string) $user->customer->_id;

            // ✅ Validate and sanitize product IDs
            $sanitizedItems = [];
            foreach ($validated['items'] as $item) {
                $productId = QuerySanitizer::sanitizeMongoId($item['product_id']);

                if (!$productId) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid product ID: {$item['product_id']}",
                    ], 400);
                }

                // ✅ Verify product exists and has stock
                $product = Product::where('_id', $productId)->first();

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product not found: {$productId}",
                    ], 404);
                }

                if ($product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}",
                    ], 400);
                }

                $sanitizedItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'subtotal' => (float) $item['unit_price'] * (int) $item['quantity'],
                ];
            }

            // ✅ Calculate totals
            $subtotal = array_sum(array_column($sanitizedItems, 'subtotal'));
            $tax = $subtotal * 0.1; // 10% tax
            $shipping = $subtotal > 100 ? 0 : 15.99; // Free shipping over $100
            $total = $subtotal + $tax + $shipping;

            // ✅ Create order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customerId, // ✅ Auto-extracted from JWT
                'user_id' => (string) $user->_id,
                'items' => $sanitizedItems,
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            // ✅ Reduce stock quantity for each item
            foreach ($sanitizedItems as $item) {
                Product::where('_id', $item['product_id'])
                    ->decrement('stock_quantity', $item['quantity']);
            }

            Log::info('Order created successfully', [
                'order_id' => (string) $order->_id,
                'customer_id' => $customerId,
                'total' => $total,
            ]);

            Cache::tags(['orders'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->fresh(['customer.user']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Order Creation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
            ], 500);
        }
    }

    /**
     * Get single order details
     * GET /api/customer/orders/{id} or /api/admin/orders/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $orderId = QuerySanitizer::sanitizeMongoId($id);

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::where('_id', $orderId)
                ->with('customer.user')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // ✅ Verify ownership if customer
            if ($user->isCustomer() && $order->customer_id !== (string) $user->customer->_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Order Show Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
            ], 500);
        }
    }

    /**
     * Update order (admin only - status updates)
     * PUT /api/admin/orders/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $orderId = QuerySanitizer::sanitizeMongoId($id);

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::where('_id', $orderId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // ✅ Only admins can update orders
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
                'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded',
                'tracking_number' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $order->update($validator->validated());

            Cache::forget("order_{$orderId}");
            Cache::tags(['orders'])->flush();

            Log::info('Order updated', [
                'order_id' => (string) $order->_id,
                'updated_by' => (string) $user->_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Order Update Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
            ], 500);
        }
    }

    /**
     * Delete order (admin only - pending orders only)
     * DELETE /api/admin/orders/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // ✅ Only admins can delete orders
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $orderId = QuerySanitizer::sanitizeMongoId($id);

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID',
                ], 400);
            }

            $order = Order::where('_id', $orderId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete order that is not pending',
                ], 400);
            }

            // ✅ Restore stock quantities
            foreach ($order->items as $item) {
                Product::where('_id', $item['product_id'])
                    ->increment('stock_quantity', $item['quantity']);
            }

            $order->delete();

            Cache::forget("order_{$orderId}");
            Cache::tags(['orders'])->flush();

            Log::info('Order deleted', [
                'order_id' => $orderId,
                'deleted_by' => (string) $user->_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Order Delete Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
            ], 500);
        }
    }
}
