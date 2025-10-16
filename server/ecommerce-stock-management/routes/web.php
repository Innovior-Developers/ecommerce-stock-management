<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

// Only respond to exact root path
Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel API is running!',
        'timestamp' => now(),
        'environment' => config('app.env'),
        'database' => config('database.default'),
        'api_endpoints' => [
            'health' => '/api/health',
            'admin_login' => '/api/admin/login (POST)',
            'customer_login' => '/api/customer/login (POST)',
            'customer_register' => '/api/customer/register (POST)',
            'products' => '/api/products',
            'test' => '/api/test'
        ]
    ]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Test routes
Route::get('/test-mongo', [TestController::class, 'testMongoConnection']);
Route::get('/test-redis', [TestController::class, 'testRedisConnection']);
Route::get('/test-products', [TestController::class, 'getAllProducts']);
Route::get('/test-full-stack', [TestController::class, 'testFullStack']);

Route::get('/health', function() {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'services' => [
            'laravel' => 'running',
            'mongodb' => config('database.default') === 'mongodb' ? 'configured' : 'not configured',
            'redis' => config('cache.default') === 'redis' ? 'configured' : 'not configured'
        ]
    ]);
});