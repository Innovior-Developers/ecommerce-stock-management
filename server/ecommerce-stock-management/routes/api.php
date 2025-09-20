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
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::get('/products', [ProductController::class, 'index']); // Admin can see all

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

// JWT Test Route - Fixed version with better error handling
Route::get('/test-jwt', function () {
    try {
        // Check JWT configuration first
        $jwtConfig = [
            'ttl' => config('jwt.ttl'),
            'ttl_type' => gettype(config('jwt.ttl')),
            'refresh_ttl' => config('jwt.refresh_ttl'),
            'refresh_ttl_type' => gettype(config('jwt.refresh_ttl')),
            'secret_exists' => !empty(config('jwt.secret')),
        ];

        // Validate TTL values are integers
        if (!is_int(config('jwt.ttl')) || !is_int(config('jwt.refresh_ttl'))) {
            return response()->json([
                'success' => false,
                'error' => 'JWT TTL values must be integers',
                'config_debug' => $jwtConfig,
                'fix' => 'Check config/jwt.php and ensure TTL values are cast to (int)'
            ], 500);
        }

        // Get or create test user
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('test123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Generate JWT token
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // Get token payload for debugging
        $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();

        return response()->json([
            'success' => true,
            'message' => 'JWT token generated successfully',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert to seconds
            'expires_at' => date('Y-m-d H:i:s', $payload->get('exp')),
            'issued_at' => date('Y-m-d H:i:s', $payload->get('iat')),
            'config_debug' => $jwtConfig
        ]);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json([
            'success' => false,
            'error' => 'JWT Error: ' . $e->getMessage(),
            'error_type' => 'JWT_EXCEPTION',
            'config_debug' => $jwtConfig ?? null
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'General Error: ' . $e->getMessage(),
            'error_type' => 'GENERAL_EXCEPTION',
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'config_debug' => $jwtConfig ?? null
        ], 500);
    }
});
