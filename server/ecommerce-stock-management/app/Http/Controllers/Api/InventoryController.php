<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\QuerySanitizer;

class InventoryController extends Controller
{
    public function stockLevels()
    {
        try {
            $stockLevels = Product::select('_id', 'name', 'sku', 'stock_quantity', 'category', 'price')
                ->where('stock_quantity', '>', 0)
                ->orderBy('stock_quantity', 'asc')
                ->get()
                ->map(function ($product) {
                    return [
                        '_id' => $product->_id,
                        'id' => $product->_id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'category' => $product->category,
                        'price' => $product->price,
                        'current_stock' => $product->stock_quantity,
                        'status' => $this->getStockStatus($product->stock_quantity),
                        'status_color' => $this->getStockStatusColor($product->stock_quantity),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $stockLevels,
                'total_products' => $stockLevels->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching stock levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock levels'
            ], 500);
        }
    }

    public function lowStock()
    {
        try {
            $lowStockThreshold = 10;

            $lowStockProducts = Product::where('stock_quantity', '<=', $lowStockThreshold)
                ->where('stock_quantity', '>=', 0)
                ->select('_id', 'name', 'sku', 'stock_quantity', 'category', 'price')
                ->orderBy('stock_quantity', 'asc')
                ->get()
                ->map(function ($product) {
                    return [
                        '_id' => $product->_id,
                        'id' => $product->_id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'category' => $product->category,
                        'current_stock' => $product->stock_quantity,
                        'price' => $product->price,
                        'status' => $this->getStockStatus($product->stock_quantity),
                        'action_required' => $product->stock_quantity <= 5 ? 'urgent_reorder' : 'reorder_soon',
                        'priority' => $product->stock_quantity === 0 ? 'critical' : ($product->stock_quantity <= 5 ? 'high' : 'medium'),
                    ];
                });

            return response()->json([
                'success' => true,
                'count' => $lowStockProducts->count(),
                'data' => $lowStockProducts,
                'threshold' => $lowStockThreshold,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching low stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch low stock products'
            ], 500);
        }
    }

    public function updateStock(Request $request, $id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $validated = $request->validate([
                'quantity' => 'required|integer|min:0',
                'operation' => 'required|string|in:add,subtract,set',
                'reason' => 'nullable|string|max:500',
            ]);

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $oldQuantity = $product->stock_quantity;
            $newQuantity = 0;

            switch ($validated['operation']) {
                case 'add':
                    $newQuantity = $oldQuantity + $validated['quantity'];
                    break;
                case 'subtract':
                    $newQuantity = max(0, $oldQuantity - $validated['quantity']);
                    break;
                case 'set':
                    $newQuantity = max(0, $validated['quantity']);
                    break;
            }

            $product->update([
                'stock_quantity' => $newQuantity
            ]);

            // ✅ SANITIZE reason
            $reason = QuerySanitizer::sanitize($validated['reason'] ?? 'Manual adjustment');

            // ✅ FIX: Get authenticated user from request
            $user = $request->user(); // This works with JWT middleware
            $performedBy = $user ? $user->name : 'System';

            // Create stock movement record
            $stockMovement = [
                'product_id' => $sanitizedId,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'change' => $newQuantity - $oldQuantity,
                'operation' => $validated['operation'],
                'reason' => $reason,
                'performed_by' => $performedBy, // ✅ Fixed
                'performed_by_id' => $user ? $user->_id : null, // ✅ Add user ID too
                'timestamp' => now(),
            ];

            // Clear cache
            Cache::forget('stock_levels');
            Cache::forget('low_stock_products');
            Cache::forget("product_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'data' => [
                    '_id' => $product->_id,
                    'id' => $product->_id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'old_stock' => $oldQuantity,
                    'new_stock' => $newQuantity,
                    'change' => $newQuantity - $oldQuantity,
                    'status' => $this->getStockStatus($newQuantity),
                    'operation' => $validated['operation'],
                ],
                'stock_movement' => $stockMovement,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock'
            ], 500);
        }
    }

    /**
     * Get stock status based on quantity
     */
    private function getStockStatus($quantity)
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        } elseif ($quantity <= 5) {
            return 'critical_low';
        } elseif ($quantity <= 10) {
            return 'low';
        } elseif ($quantity <= 50) {
            return 'moderate';
        } else {
            return 'good';
        }
    }

    /**
     * Get color code for stock status
     */
    private function getStockStatusColor($quantity)
    {
        if ($quantity <= 0) {
            return 'red';
        } elseif ($quantity <= 5) {
            return 'orange';
        } elseif ($quantity <= 10) {
            return 'yellow';
        } elseif ($quantity <= 50) {
            return 'blue';
        } else {
            return 'green';
        }
    }
}