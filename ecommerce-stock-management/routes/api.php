<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OAuthController;

// OAuth routes (using custom OAuth controller for MongoDB)
Route::post('/register', [OAuthController::class, 'register']);
Route::post('/login', [OAuthController::class, 'login']);
Route::post('/refresh-token', [OAuthController::class, 'refreshToken']);

// Protected routes (require Passport token)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [OAuthController::class, 'logout']);
    Route::get('/user', [OAuthController::class, 'user']);

    // Product routes
    Route::apiResource('products', ProductController::class);

    // Order routes
    Route::apiResource('orders', OrderController::class);

    // Inventory routes
    Route::prefix('inventory')->group(function () {
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::put('/{id}', [InventoryController::class, 'updateStock']);
    });
});

// Public routes (no authentication required)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
