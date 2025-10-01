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

// Debug S3 config route
Route::get('/debug-s3-config', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'filesystem_default' => config('filesystems.default'),
        'aws_config' => [
            'bucket' => config('filesystems.disks.s3.bucket'),
            'region' => config('filesystems.disks.s3.region'),
            'key' => config('filesystems.disks.s3.key') ? 'SET' : 'NOT SET',
            'secret' => config('filesystems.disks.s3.secret') ? 'SET' : 'NOT SET',
        ],
        'env_vars' => [
            'AWS_BUCKET' => env('AWS_BUCKET'),
            'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION'),
            'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET',
            'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET',
        ],
        's3_driver_exists' => class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter'),
        'aws_sdk_exists' => class_exists('Aws\S3\S3Client'),
    ]);
});

// Test upload limits
Route::post('/test-upload-limits', function (Request $request) {
    return response()->json([
        'php_config' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ],
        'request_size' => strlen(file_get_contents('php://input')) . ' bytes',
        'files_received' => $request->hasFile('images') ? count($request->file('images')) : 0,
    ]);
});

// Add this route for testing S3
Route::get('/test-s3', function () {
    try {
        // Test S3 connection
        $testContent = 'Test file content - ' . now();
        $testPath = 'test/test-file-' . time() . '.txt';

        Log::info('Testing S3 connection', [
            'test_path' => $testPath,
            'config_check' => [
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'key_exists' => !empty(config('filesystems.disks.s3.key')),
                'secret_exists' => !empty(config('filesystems.disks.s3.secret')),
                'driver' => config('filesystems.disks.s3.driver')
            ]
        ]);

        $result = Storage::disk('s3')->put($testPath, $testContent);

        if ($result) {
            // ✅ FIX: Manual URL construction instead of using url() method
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            $testUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$testPath}";

            // Test if file exists
            $exists = Storage::disk('s3')->exists($testPath);

            // Clean up test file
            if ($exists) {
                Storage::disk('s3')->delete($testPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'S3 connection successful',
                'test_url' => $testUrl,
                'file_existed' => $exists,
                'config' => [
                    'bucket' => $bucket,
                    'region' => $region,
                    'driver' => config('filesystems.disks.s3.driver'),
                    'key_set' => !empty(config('filesystems.disks.s3.key')),
                    'secret_set' => !empty(config('filesystems.disks.s3.secret'))
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload test file to S3'
            ], 500);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'S3 connection failed',
            'error' => $e->getMessage(),
            'config_debug' => [
                'filesystems_config' => config('filesystems.disks.s3'),
                'aws_configured' => class_exists('Aws\S3\S3Client')
            ]
        ], 500);
    }
});

// Add this route at the end of the file, before the last closing brace if any.
Route::post('/debug-file-request', function (Request $request) {
    $filesData = [];
    $allFiles = $request->allFiles();

    foreach ($allFiles as $key => $file) {
        if (is_array($file)) {
            foreach ($file as $index => $f) {
                if ($f instanceof Illuminate\Http\UploadedFile) {
                    $filesData[] = [
                        'key' => "{$key}[{$index}]",
                        'original_name' => $f->getClientOriginalName(),
                        'size' => $f->getSize(),
                        'mime_type' => $f->getMimeType(),
                        'is_valid' => $f->isValid(),
                        'error' => $f->getError(),
                        'error_message' => $f->getErrorMessage(),
                    ];
                }
            }
        } elseif ($file instanceof Illuminate\Http\UploadedFile) {
            $filesData[] = [
                'key' => $key,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
            ];
        }
    }

    return response()->json([
        'message' => 'File Request Debug Information',
        'request_headers' => $request->headers->all(),
        'php_ini_values' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ],
        'laravel_file_detection' => [
            'hasFile("images")' => $request->hasFile('images'),
            'allFiles_keys' => array_keys($allFiles),
        ],
        'processed_files' => $filesData,
        'raw_post_data_size' => strlen($request->getContent()) . ' bytes',
        'request_all' => $request->all(),
    ]);
});

// Add this route at the end of your api.php file
Route::post('/test-image-upload', function (Request $request) {
    $allFiles = $request->allFiles();
    $result = [
        'all_files_count' => count($allFiles),
        'all_files_keys' => array_keys($allFiles),
        'has_images' => isset($allFiles['images']),
        'processed_files' => []
    ];

    if (isset($allFiles['images'])) {
        $imageFiles = $allFiles['images'];
        foreach ($imageFiles as $index => $file) {
            $result['processed_files'][] = [
                'index' => $index,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ];
        }
    }

    return response()->json($result);
});
