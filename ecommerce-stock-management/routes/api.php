<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API routes are working correctly!',
        'timestamp' => now(),
        'endpoint' => '/api/test'
    ]);
});

// Auth routes (for login, register, etc.)
Route::prefix('auth')->group(function () {
    // Admin authentication
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);

    // Customer authentication
    Route::post('/customer/register', [AuthController::class, 'customerRegister']);
    Route::post('/customer/login', [AuthController::class, 'customerLogin']);

    // Social authentication
    Route::get('/social/{provider}', [SocialAuthController::class, 'redirectToProvider']);
    Route::get('/social/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// Admin routes (for managing resources)
Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
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

// Customer routes
Route::prefix('customer')->middleware(['auth:sanctum', 'ability:customer'])->group(function () {
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
});

// Public routes (no authentication required)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// Health check
Route::get('/health', function() {
    return response()->json([
        'success' => true,
        'message' => 'API Health Check Successful',
        'status' => 'healthy',
        'timestamp' => now(),
        'auth' => 'sanctum',
        'routes' => [
            'admin_login' => '/api/auth/admin/login',
            'customer_login' => '/api/auth/customer/login',
            'customer_register' => '/api/auth/customer/register',
            'social_google' => '/api/auth/social/google',
            'social_github' => '/api/auth/social/github',
        ]
    ]);
});
