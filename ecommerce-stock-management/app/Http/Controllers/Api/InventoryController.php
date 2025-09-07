<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    public function stockLevels()
    {
        $stockLevels = Cache::remember('stock_levels', 300, function () {
            return Product::select('_id', 'name', 'sku', 'stock_quantity', 'category')
                         ->where('stock_quantity', '>', 0)
                         ->orderBy('stock_quantity', 'asc')
                         ->get()
                         ->map(function ($product) {
                             return [
                                 'id' => $product->_id,
                                 'name' => $product->name,
                                 'sku' => $product->sku,
                                 'category' => $product->category,
                                 'current_stock' => $product->stock_quantity,
                                 'status' => $this->getStockStatus($product->stock_quantity)
                             ];
                         });
        });

        return response()->json([
            'success' => true,
            'data' => $stockLevels
        ]);
    }

    public function lowStock()
    {
        $lowStockThreshold = 10;

        $lowStockProducts = Cache::remember('low_stock_products', 300, function () use ($lowStockThreshold) {
            return Product::where('stock_quantity', '<=', $lowStockThreshold)
                         ->where('stock_quantity', '>=', 0)
                         ->select('_id', 'name', 'sku', 'stock_quantity', 'category', 'price')
                         ->orderBy('stock_quantity', 'asc')
                         ->get()
                         ->map(function ($product) {
                             return [
                                 'id' => $product->_id,
                                 'name' => $product->name,
                                 'sku' => $product->sku,
                                 'category' => $product->category,
                                 'current_stock' => $product->stock_quantity,
                                 'price' => $product->price,
                                 'status' => $this->getStockStatus($product->stock_quantity),
                                 'action_required' => $product->stock_quantity <= 5 ? 'urgent_reorder' : 'reorder_soon'
                             ];
                         });
        });

        return response()->json([
            'success' => true,
            'count' => $lowStockProducts->count(),
            'data' => $lowStockProducts
        ]);
    }

    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'operation' => 'required|string|in:add,subtract,set',
            'reason' => 'sometimes|string',
        ]);

        $product = Product::findOrFail($id);
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

        // Log stock movement (you can create a StockMovement model later)
        $stockMovement = [
            'product_id' => $id,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'change' => $newQuantity - $oldQuantity,
            'operation' => $validated['operation'],
            'reason' => $validated['reason'] ?? 'Manual adjustment',
            'timestamp' => now(),
        ];

        // Clear cache
        Cache::forget('stock_levels');
        Cache::forget('low_stock_products');
        Cache::forget("product_{$id}");

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'product' => [
                'id' => $product->_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'old_stock' => $oldQuantity,
                'new_stock' => $newQuantity,
                'change' => $newQuantity - $oldQuantity,
                'status' => $this->getStockStatus($newQuantity)
            ],
            'stock_movement' => $stockMovement
        ]);
    }

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
}
