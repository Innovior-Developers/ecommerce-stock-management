<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(serialize($request->all()));

        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Product::query();

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'regex', "/$search/i")
                      ->orWhere('description', 'regex', "/$search/i")
                      ->orWhere('sku', 'regex', "/$search/i");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                $query->where('status', 'active'); // Default to active products
            }

            // Stock filter
            if ($request->has('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            return $query->paginate($request->get('per_page', 20));
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'category' => 'required|string',
            'stock_quantity' => 'integer|min:0',
            'status' => 'in:active,inactive',
            'image_url' => 'nullable|url',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        // Set defaults
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;

        $product = Product::create($validated);

        // Clear cache
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function show($id)
    {
        $product = Cache::remember("product_{$id}", 600, function () use ($id) {
            return Product::with('inventory')->findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'sku' => ['string', Rule::unique('products')->ignore($id, '_id')],
            'category' => 'string',
            'stock_quantity' => 'integer|min:0',
            'status' => 'in:active,inactive',
            'image_url' => 'nullable|url',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $product->update($validated);

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Check if product has orders
        $hasOrders = \App\Models\Order::where('items.product_id', $id)->exists();
        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product that has been ordered',
            ], 400);
        }

        $product->delete();

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
