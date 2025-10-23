<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\QuerySanitizer;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Order::with(['customer.user']);

            // ✅ SANITIZE status filter
            if ($request->has('status')) {
                $status = QuerySanitizer::sanitize($request->get('status'));
                if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
                    $query->where('status', $status);
                }
            }

            // ✅ SANITIZE customer_id filter
            if ($request->has('customer_id')) {
                $customerId = QuerySanitizer::sanitizeMongoId($request->get('customer_id'));
                if ($customerId) {
                    $query->where('customer_id', $customerId);
                }
            }

            // Date filters
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $orders = $query->latest()->get();

            // ✅ Ensure consistent ID format
            $ordersData = $orders->map(function ($order) {
                return [
                    '_id' => $order->_id,
                    'id' => $order->_id, // ✅ Include both
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'customer' => $order->customer ? [
                        '_id' => $order->customer->_id,
                        'id' => $order->customer->_id,
                        'name' => $order->customer->getFullNameAttribute(),
                        'email' => $order->customer->user->email ?? null,
                    ] : null,
                    'items' => $order->items,
                    'shipping_address' => $order->shipping_address,
                    'billing_address' => $order->billing_address,
                    'payment' => $order->payment,
                    'status' => $order->status,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'notes' => $order->notes,
                    'tracking_number' => $order->tracking_number,
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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'shipping_address' => 'required|array',
                'shipping_address.street' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.country' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'payment' => 'required|array',
                'payment.method' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            // ✅ VALIDATE customer_id
            $customerId = QuerySanitizer::sanitizeMongoId($validated['customer_id']);
            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID format'
                ], 400);
            }

            // ✅ Verify customer exists
            if (!Customer::where('_id', $customerId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // ✅ SANITIZE and validate product IDs in items
            foreach ($validated['items'] as $key => $item) {
                $productId = QuerySanitizer::sanitizeMongoId($item['product_id']);
                if (!$productId) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid product ID in item {$key}"
                    ], 400);
                }
                $validated['items'][$key]['product_id'] = $productId;
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.1; // 10% tax
            $shipping_cost = 10.00; // Fixed shipping
            $total = $subtotal + $tax + $shipping_cost;

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
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
            ]);

            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    '_id' => $order->_id,
                    'id' => $order->_id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'status' => $order->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID format'
                ], 400);
            }

            $order = Order::with('customer.user')
                ->where('_id', $sanitizedId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    '_id' => $order->_id,
                    'id' => $order->_id,
                    'order_number' => $order->order_number,
                    'customer' => $order->customer ? [
                        '_id' => $order->customer->_id,
                        'id' => $order->customer->_id,
                        'name' => $order->customer->getFullNameAttribute(),
                        'email' => $order->customer->user->email ?? null,
                    ] : null,
                    'items' => $order->items,
                    'shipping_address' => $order->shipping_address,
                    'billing_address' => $order->billing_address,
                    'payment' => $order->payment,
                    'status' => $order->status,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'notes' => $order->notes,
                    'tracking_number' => $order->tracking_number,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID format'
                ], 400);
            }

            $order = Order::where('_id', $sanitizedId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
                'payment' => 'sometimes|array',
                'payment.status' => 'sometimes|string|in:pending,paid,failed,refunded',
                'shipping_address' => 'sometimes|array',
                'tracking_number' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            // ✅ SANITIZE string inputs
            if (isset($validated['tracking_number'])) {
                $validated['tracking_number'] = QuerySanitizer::sanitize($validated['tracking_number']);
            }
            if (isset($validated['notes'])) {
                $validated['notes'] = QuerySanitizer::sanitize($validated['notes']);
            }

            // Update timestamps based on status
            if (isset($validated['status'])) {
                if ($validated['status'] === 'shipped' && !$order->shipped_at) {
                    $validated['shipped_at'] = now();
                }
                if ($validated['status'] === 'delivered' && !$order->delivered_at) {
                    $validated['delivered_at'] = now();
                }
            }

            $order->update($validated);

            Cache::forget("order_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => [
                    '_id' => $order->_id,
                    'id' => $order->_id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'updated_at' => $order->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID format'
                ], 400);
            }

            $order = Order::where('_id', $sanitizedId)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete order that is not in pending status'
                ], 400);
            }

            $order->delete();

            Cache::forget("order_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order'
            ], 500);
        }
    }
}