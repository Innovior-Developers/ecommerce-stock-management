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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Public routes
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/admin/login', [JWTAuthController::class, 'adminLogin']);
    Route::post('/customer/login', [JWTAuthController::class, 'customerLogin']);
    Route::post('/customer/register', [JWTAuthController::class, 'customerRegister']);

    // Protected auth routes (require token)
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/user', [JWTAuthController::class, 'me']);
        Route::post('/logout', [JWTAuthController::class, 'logout']);
        Route::post('/refresh', [JWTAuthController::class, 'refresh']);
    });
});

// Public product routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// Admin routes (require JWT + admin role)
Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {
    // Product management
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('products/{product}', [ProductController::class, 'update']); // ✅ Change PUT/PATCH to POST
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    // Category management
    Route::apiResource('categories', CategoryController::class);

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

    // Additional image management routes
    Route::post('/products/{id}/images', [ProductController::class, 'uploadImages']);
    Route::delete('/products/{id}/images', [ProductController::class, 'deleteImage']);
});

// Customer routes (require JWT + customer role)
Route::prefix('customer')->middleware('jwt.auth')->group(function () {
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
    // Add more customer-specific routes here
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => config('database.default'),
        'cache' => config('cache.default'),
    ]);
});

// Debug route (development only)
Route::middleware('jwt.auth')->group(function () {
    Route::get('/debug-auth', [DebugController::class, 'authInfo']);
    Route::post('/debug-refresh', [DebugController::class, 'testRefresh']);
});

// Make sure these routes are in your api.php file
Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {
    // Category management
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('categories/tree', [CategoryController::class, 'tree']); // Optional: for tree view
});