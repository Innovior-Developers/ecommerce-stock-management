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
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;

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
    Route::get('/user', [JWTAuthController::class, 'user']);
    Route::post('/logout', [JWTAuthController::class, 'logout']);
    Route::post('/refresh', [JWTAuthController::class, 'refresh']);
    Route::put('/password', [JWTAuthController::class, 'updatePassword']);
});

// ========================================
// ADMIN ROUTES (JWT + Admin Role)
// ========================================

Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {

    // Product management
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    // Product images
    Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
    Route::delete('/products/{id}/images', [ProductController::class, 'deleteImage']);

    // Category management
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);

    // Customer management (Admin can view/manage customers)
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{id}', [CustomerController::class, 'show']);
    Route::put('customers/{id}', [CustomerController::class, 'update']);
    Route::delete('customers/{id}', [CustomerController::class, 'destroy']);

    // ✅ Admin Order Management (View/Update orders, NOT create)
    Route::get('orders', [OrderController::class, 'index']); // View all orders
    Route::get('orders/{id}', [OrderController::class, 'show']); // View specific order
    Route::put('orders/{id}', [OrderController::class, 'update']); // Update order status
    Route::delete('orders/{id}', [OrderController::class, 'destroy']); // Delete order

    // Inventory management
    Route::prefix('inventory')->group(function () {
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::put('/{id}', [InventoryController::class, 'updateStock']);
    });
});

// ========================================
// ✅ CUSTOMER ROUTES (JWT + Customer Role)
// ========================================

Route::prefix('customer')->middleware('jwt.auth')->group(function () {
    // Profile management
    Route::put('/profile', [CustomerController::class, 'updateProfile']);

    // ✅ Customer creates their own orders
    Route::post('/orders', [OrderController::class, 'store']);

    // ✅ Customer views their own orders
    Route::get('/orders', [OrderController::class, 'myOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

// ========================================
// ✅ PAYMENT ROUTES (JWT + Rate Limited)
// ========================================

Route::prefix('payment')->middleware(['jwt.auth', 'throttle:payment'])->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate']);
    Route::post('/confirm', [PaymentController::class, 'confirm']);
    Route::get('/status/{id}', [PaymentController::class, 'status']);
    Route::get('/history', [PaymentController::class, 'history']);
});

// ========================================
// ✅ WEBHOOK ROUTES (No JWT, Rate Limited)
// ========================================

Route::prefix('webhooks')->group(function () {
    Route::post('/stripe', [PaymentWebhookController::class, 'stripe']);
    Route::post('/paypal', [PaymentWebhookController::class, 'paypal']);
    Route::post('/payhere', [PaymentWebhookController::class, 'payhere']);
});

// ========================================
// DEBUG ROUTES (Development Only)
// ========================================

if (config('app.env') !== 'production') {
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/debug-auth', [DebugController::class, 'authInfo']);
        Route::post('/debug-refresh', [DebugController::class, 'testRefresh']);
    });
}
