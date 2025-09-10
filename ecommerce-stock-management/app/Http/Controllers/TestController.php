<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function testMongoConnection()
    {
        try {
            // Test MongoDB connection
            $product = Product::create([
                'name' => 'Test Product ' . now(),
                'description' => 'This is a test product for MongoDB connection',
                'price' => 99.99,
                'sku' => 'TEST-' . rand(1000, 9999),
                'category' => 'Electronics',
                'stock_quantity' => 10,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'MongoDB connection successful!',
                'database' => config('database.connections.mongodb.database'),
                'product_created' => $product,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'MongoDB connection failed!',
                'error' => $e->getMessage(),
                'config' => [
                    'connection' => config('database.default'),
                    'dsn' => config('database.connections.mongodb.dsn'),
                    'database' => config('database.connections.mongodb.database')
                ]
            ], 500);
        }
    }

    public function testRedisConnection()
    {
        try {
            // Test Redis connection
            $testKey = 'test_redis_' . time();
            $testValue = 'Redis is working at ' . now();

            Redis::set($testKey, $testValue);
            $retrievedValue = Redis::get($testKey);

            // Clean up
            Redis::del($testKey);

            return response()->json([
                'success' => true,
                'message' => 'Redis connection successful!',
                'test_data' => [
                    'key' => $testKey,
                    'stored_value' => $testValue,
                    'retrieved_value' => $retrievedValue,
                    'match' => $testValue === $retrievedValue
                ],
                'redis_config' => [
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                    'cache_store' => config('cache.default')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redis connection failed!',
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
                'products' => $products,
                'database_info' => [
                    'connection' => config('database.default'),
                    'database' => config('database.connections.mongodb.database')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testFullStack()
    {
        try {
            // Test MongoDB
            $product = Product::create([
                'name' => 'Full Stack Test Product',
                'description' => 'Testing full stack integration',
                'price' => 199.99,
                'sku' => 'FULL-' . rand(1000, 9999),
                'category' => 'Test',
                'stock_quantity' => 5,
                'status' => 'active'
            ]);

            // Test Redis Cache
            $cacheKey = 'test_product_' . $product->_id;
            cache()->put($cacheKey, $product->toArray(), 300); // 5 minutes
            $cachedProduct = cache()->get($cacheKey);

            // Test Redis directly
            $redisKey = 'direct_test_' . time();
            Redis::setex($redisKey, 60, json_encode(['test' => 'direct redis works']));
            $redisData = json_decode(Redis::get($redisKey), true);

            return response()->json([
                'success' => true,
                'message' => 'Full stack test successful!',
                'tests' => [
                    'mongodb' => [
                        'status' => 'success',
                        'product_created' => $product
                    ],
                    'cache' => [
                        'status' => 'success',
                        'cached_data' => $cachedProduct,
                        'cache_driver' => config('cache.default')
                    ],
                    'redis_direct' => [
                        'status' => 'success',
                        'redis_data' => $redisData
                    ]
                ],
                'environment' => [
                    'app_env' => config('app.env'),
                    'database' => config('database.default'),
                    'cache' => config('cache.default'),
                    'session' => config('session.driver')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Full stack test failed!',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
