<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JWTAuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\DebugController;

// Public routes
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => config('database.default'),
        'cache' => config('cache.default'),
    ]);
});

// Public product and category routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// ========================================
// AUTHENTICATION ROUTES (Rate Limited)
// ========================================

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/admin/login', [JWTAuthController::class, 'adminLogin']);
    Route::post('/customer/login', [JWTAuthController::class, 'customerLogin']);
    Route::post('/customer/register', [JWTAuthController::class, 'customerRegister']);
});

// Protected auth routes (require JWT)
Route::prefix('auth')->middleware('jwt.auth')->group(function () {
    Route::post('/logout', [JWTAuthController::class, 'logout']);
    Route::post('/refresh', [JWTAuthController::class, 'refresh']);
    Route::get('/user', [JWTAuthController::class, 'user']); // ✅ FIXED: Changed from 'me' to 'user'
});

// ========================================
// ADMIN ROUTES (JWT + Admin Role)
// ========================================

Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {

    // Product management
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('products/{product}', [ProductController::class, 'update']); // ✅ Using POST for file uploads
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    // Product images
    Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
    Route::delete('/products/{id}/images', [ProductController::class, 'deleteImage']);

    // ✅ FIXED: Remove duplicate category routes, keep only one set
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);

    // Customer management
    Route::apiResource('customers', CustomerController::class);

    // Order management
    Route::apiResource('orders', OrderController::class);

    // Inventory management
    Route::prefix('inventory')->group(function () {
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::put('/{id}', [InventoryController::class, 'updateStock']);
    });
});

// ========================================
// CUSTOMER ROUTES (JWT + Customer Role)
// ========================================

Route::prefix('customer')->middleware('jwt.auth')->group(function () {
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
});

// ========================================
// DEBUG ROUTES (Development Only)
// ========================================

// ✅ Only enable if NOT in production
if (config('app.env') !== 'production') {
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/debug-auth', [DebugController::class, 'authInfo']);
        Route::post('/debug-refresh', [DebugController::class, 'testRefresh']);
    });
}
