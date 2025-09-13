<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;

// Test route to verify API is working
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API routes are working correctly!',
        'timestamp' => now(),
        'endpoint' => '/api/test'
    ]);
});

// Admin routes - using Passport OAuth
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'adminLogin']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'user']);

        // Admin-only routes
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('orders', OrderController::class);

        // Inventory management
        Route::prefix('inventory')->group(function () {
            Route::get('/stock-levels', [InventoryController::class, 'stockLevels']);
            Route::get('/low-stock', [InventoryController::class, 'lowStock']);
            Route::put('/{id}', [InventoryController::class, 'updateStock']);
        });
    });
});

// Customer routes - using Passport OAuth
Route::prefix('customer')->group(function () {
    Route::post('/register', [OAuthController::class, 'register']);
    Route::post('/login', [OAuthController::class, 'login']);
    Route::post('/refresh-token', [OAuthController::class, 'refreshToken']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [OAuthController::class, 'logout']);
        Route::get('/profile', [OAuthController::class, 'user']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);
    });
});

// Public routes (no authentication required)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// Health check specific to API
Route::get('/health', function() {
    return response()->json([
        'success' => true,
        'message' => 'API Health Check Successful',
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'laravel' => 'running',
            'mongodb' => config('database.default') === 'mongodb' ? 'connected' : 'not configured',
            'redis' => config('cache.default') === 'redis' ? 'connected' : 'not configured'
        ],
        'routes' => [
            'admin_login' => '/api/admin/login',
            'customer_login' => '/api/customer/login',
            'customer_register' => '/api/customer/register',
            'products' => '/api/products',
            'categories' => '/api/categories'
        ]
    ]);
});
