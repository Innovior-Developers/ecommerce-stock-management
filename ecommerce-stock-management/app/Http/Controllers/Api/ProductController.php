<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(serialize($request->all()));

        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Product::query();

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('search')) {
                $query->where('name', 'regex', '/' . $request->search . '/i');
            }

            return $query->paginate(20);
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'category' => 'required|string',
        ]);

        $product = Product::create($validated);

        // Clear cache
        Cache::tags(['products'])->flush();

        return response()->json($product, 201);
    }

    public function show($id)
    {
        $product = Cache::remember("product_{$id}", 600, function () use ($id) {
            return Product::findOrFail($id);
        });

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'category' => 'string',
        ]);

        $product->update($validated);

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
