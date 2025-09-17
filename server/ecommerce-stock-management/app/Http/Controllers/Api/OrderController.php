<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'orders_' . md5(serialize($request->all()));

        $orders = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Order::with('customer');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            return $query->orderBy('created_at', 'desc')->paginate(20);
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|string',
            'items' => 'required|array',
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
        ]);

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
            'customer_id' => $validated['customer_id'],
            'items' => $validated['items'],
            'shipping_address' => $validated['shipping_address'],
            'billing_address' => $validated['shipping_address'], // Same as shipping for now
            'payment' => array_merge($validated['payment'], ['status' => 'pending']),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
        ]);

        // Clear cache
        Cache::tags(['orders'])->flush();

        return response()->json($order, 201);
    }

    public function show($id)
    {
        $order = Cache::remember("order_{$id}", 300, function () use ($id) {
            return Order::with('customer')->findOrFail($id);
        });

        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
            'payment.status' => 'sometimes|string|in:pending,paid,failed',
            'shipping_address' => 'sometimes|array',
            'items' => 'sometimes|array',
        ]);

        $order->update($validated);

        // Clear cache
        Cache::forget("order_{$id}");
        Cache::tags(['orders'])->flush();

        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        // Only allow deletion of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete order that is not pending'
            ], 400);
        }

        $order->delete();

        // Clear cache
        Cache::forget("order_{$id}");
        Cache::tags(['orders'])->flush();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
