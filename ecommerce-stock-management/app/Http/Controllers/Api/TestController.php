<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class TestController extends Controller
{
    public function testMongoConnection()
    {
        try {
            // Test connection by creating a test product
            $product = Product::create([
                'name' => 'Test Product',
                'description' => 'This is a test product',
                'price' => 99.99,
                'sku' => 'TEST-001',
                'category' => 'Electronics',
                'stock_quantity' => 10,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'MongoDB connection successful!',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'MongoDB connection failed!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllProducts()
    {
        try {
            $products = Product::all();

            return response()->json([
                'success' => true,
                'count' => $products->count(),
                'products' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
