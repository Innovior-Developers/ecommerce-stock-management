<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JWTAuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InventoryController;

// ✅ Public routes
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

// ✅ Auth routes (public) - NO TRAILING SLASHES
Route::prefix('auth')->group(function () {
    Route::post('admin/login', [JWTAuthController::class, 'adminLogin']);
    Route::post('customer/login', [JWTAuthController::class, 'customerLogin']);
    Route::post('customer/register', [JWTAuthController::class, 'customerRegister']);

    // Protected auth routes
    Route::middleware('jwt.auth')->group(function () {
        Route::get('user', [JWTAuthController::class, 'me']);
        Route::post('logout', [JWTAuthController::class, 'logout']);
        Route::post('refresh', [JWTAuthController::class, 'refresh']);
    });
});

// ✅ Public product routes
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);

// ✅ Admin routes
Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class);

    Route::prefix('inventory')->group(function () {
        Route::get('stock-levels', [InventoryController::class, 'stockLevels']);
        Route::get('low-stock', [InventoryController::class, 'lowStock']);
        Route::put('{id}', [InventoryController::class, 'updateStock']);
    });
});

// ✅ Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => config('database.default'),
    ]);
});
