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
    Route::apiResource('products', ProductController::class);

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

// // Add this to routes/api.php for testing
// Route::get('/test-s3', function () {
//     try {
//         // Test S3 connection
//         $disk = Storage::disk('s3');

//         // Try to list files (this will test connection)
//         $files = $disk->files();

//         // Test URL generation
//         $testPath = 'test/sample.jpg';
//         $url = $disk->url($testPath);

//         return response()->json([
//             'success' => true,
//             's3_connected' => true,
//             'sample_url' => $url,
//             'config' => [
//                 'bucket' => config('filesystems.disks.s3.bucket'),
//                 'region' => config('filesystems.disks.s3.region'),
//                 'aws_url' => config('filesystems.disks.s3.url'),
//             ]
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage(),
//             'config_check' => [
//                 'bucket' => config('filesystems.disks.s3.bucket') ? 'Set' : 'Missing',
//                 'access_key' => config('filesystems.disks.s3.key') ? 'Set' : 'Missing',
//                 'secret_key' => config('filesystems.disks.s3.secret') ? 'Set' : 'Missing',
//                 'region' => config('filesystems.disks.s3.region') ? 'Set' : 'Missing',
//             ]
//         ]);
//     }
// });

// Test S3 file upload route
Route::get('/test-s3-upload', function () {
    try {
        // First, check configuration
        $config = [
            'bucket' => config('filesystems.disks.s3.bucket'),
            'region' => config('filesystems.disks.s3.region'),
            'key' => config('filesystems.disks.s3.key') ? 'SET' : 'NOT SET',
            'secret' => config('filesystems.disks.s3.secret') ? 'SET' : 'NOT SET',
            'url' => config('filesystems.disks.s3.url'),
        ];

        // Check if required config is missing
        if (
            empty($config['bucket']) || empty($config['region']) ||
            $config['key'] === 'NOT SET' || $config['secret'] === 'NOT SET'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'S3 configuration is incomplete',
                'config_status' => $config,
                'required_env_vars' => [
                    'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID') ? 'SET' : 'MISSING',
                    'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'MISSING',
                    'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION') ?: 'MISSING',
                    'AWS_BUCKET' => env('AWS_BUCKET') ?: 'MISSING',
                ]
            ]);
        }

        // Test S3 connection
        $disk = Storage::disk('s3');

        // Test file upload
        $testContent = 'This is a test file created at: ' . now()->toDateTimeString();
        $testPath = 'test/test-file-' . time() . '.txt';

        // Try to upload with explicit options
        $result = $disk->put($testPath, $testContent, [
            'visibility' => 'public',
            'ContentType' => 'text/plain',
            'CacheControl' => 'max-age=3600',
        ]);

        if ($result) {
            // Generate URL
            $url = "https://{$config['bucket']}.s3.{$config['region']}.amazonaws.com/{$testPath}";

            // Verify file exists
            $exists = $disk->exists($testPath);

            // Get file info with safe method calls
            $fileInfo = [];
            if ($exists) {
                try {
                    $fileInfo['size'] = $disk->size($testPath);
                    $fileInfo['mime_type'] = 'text/plain';
                    $fileInfo['file_extension'] = pathinfo($testPath, PATHINFO_EXTENSION);
                } catch (\Exception $e) {
                    $fileInfo['info_error'] = $e->getMessage();
                }
            }

            // Test if we can read the file back
            $canRead = false;
            $readContent = null;
            try {
                $readContent = $disk->get($testPath);
                $canRead = ($readContent === $testContent);
            } catch (\Exception $e) {
                $fileInfo['read_error'] = $e->getMessage();
            }

            // Clean up test file
            $deleted = false;
            try {
                $deleted = $disk->delete($testPath);
            } catch (\Exception $e) {
                $fileInfo['delete_error'] = $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => 'S3 upload test successful',
                'results' => [
                    'uploaded' => true,
                    'file_exists' => $exists,
                    'can_read_file' => $canRead,
                    'content_matches' => $canRead,
                    'file_info' => $fileInfo,
                    'deleted' => $deleted,
                    'test_url' => $url,
                    'test_path' => $testPath,
                ],
                'config' => $config
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'S3 upload failed - upload returned false',
                'debug_info' => [
                    'test_content_length' => strlen($testContent),
                    'test_path' => $testPath,
                    'storage_driver' => 's3',
                ],
                'config' => $config
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'error_code' => $e->getCode(),
            'config_check' => [
                'bucket' => config('filesystems.disks.s3.bucket') ?: 'MISSING',
                'region' => config('filesystems.disks.s3.region') ?: 'MISSING',
                'access_key' => config('filesystems.disks.s3.key') ? 'SET' : 'MISSING',
                'secret_key' => config('filesystems.disks.s3.secret') ? 'SET' : 'MISSING',
            ],
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
        ]);
    }
});

// Simple S3 test route
Route::get('/test-s3-simple', function () {
    try {
        $disk = Storage::disk('s3');

        // Simple test - just upload and check if it returns truthy value
        $testPath = 'simple-test/test-' . time() . '.txt';
        $result = $disk->put($testPath, 'Simple test content');

        if ($result) {
            // Try to read it back
            $content = $disk->get($testPath);

            // Clean up
            $disk->delete($testPath);

            return response()->json([
                'success' => true,
                'message' => 'Simple S3 test passed',
                'uploaded_path' => $result,
                'content_retrieved' => $content === 'Simple test content'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Simple S3 test failed'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'class' => get_class($e)
        ]);
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

// ADD THIS NEW ROUTE
Route::get('/test-aws-sdk-direct', function () {
    try {
        if (!class_exists('Aws\S3\S3Client')) {
            return response()->json(['success' => false, 'error' => 'AWS SDK not found.']);
        }

        $s3Client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);

        $bucket = env('AWS_BUCKET');
        $key = 'direct-sdk-test/test-' . time() . '.txt';

        $result = $s3Client->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => 'This is a direct test from the AWS SDK.',
            // Remove 'ACL' => 'public-read' - this causes the error
        ]);

        // Clean up the test file
        $s3Client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);

        return response()->json([
            'success' => true,
            'message' => 'Direct AWS SDK test was successful!',
            'result' => $result->toArray(),
        ]);
    } catch (\Aws\Exception\AwsException $e) {
        return response()->json([
            'success' => false,
            'error' => 'AWS SDK Exception',
            'message' => $e->getMessage(),
            'aws_error_code' => $e->getAwsErrorCode(),
            'aws_error_type' => $e->getAwsErrorType(),
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'General Exception',
            'message' => $e->getMessage(),
        ], 500);
    }
});

// Test image upload to S3 (accepts actual image files)
Route::post('/test-s3-image-upload', function (Request $request) {
    try {
        // Validate the uploaded image
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        $file = $request->file('image');

        // Check S3 configuration first
        $config = [
            'bucket' => config('filesystems.disks.s3.bucket'),
            'region' => config('filesystems.disks.s3.region'),
            'key' => config('filesystems.disks.s3.key') ? 'SET' : 'NOT SET',
            'secret' => config('filesystems.disks.s3.secret') ? 'SET' : 'NOT SET',
        ];

        if (empty($config['bucket']) || $config['key'] === 'NOT SET' || $config['secret'] === 'NOT SET') {
            return response()->json([
                'success' => false,
                'message' => 'S3 configuration is incomplete',
                'config_status' => $config
            ], 500);
        }

        $disk = Storage::disk('s3');

        // Generate unique filename
        $timestamp = time();
        $randomString = Str::random(8);
        $extension = $file->getClientOriginalExtension();
        $filename = "test-image-{$timestamp}_{$randomString}.{$extension}";
        $path = "test-uploads/{$filename}";

        // Upload to S3 WITHOUT ACL options
        $uploaded = $disk->put($path, file_get_contents($file), [
            'ContentType' => $file->getMimeType(),
            'CacheControl' => 'max-age=31536000',
            // Remove 'visibility' => 'public' - this causes the ACL error
        ]);

        if ($uploaded) {
            // Generate the public URL
            $url = "https://{$config['bucket']}.s3.{$config['region']}.amazonaws.com/{$path}";

            // Verify the file exists
            $exists = $disk->exists($path);

            // Get file information
            $fileInfo = [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'uploaded_filename' => $filename,
                'exists_in_s3' => $exists,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully to S3!',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'file_info' => $fileInfo,
                    'bucket' => $config['bucket'],
                    'region' => $config['region'],
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image to S3'
            ], 500);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage(),
            'error_class' => get_class($e),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ], 500);
    }
});

// Test product creation with image upload (simulates real product creation)
Route::post('/test-product-with-image', function (Request $request) {
    try {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'stock_quantity' => 'required|integer|min:0',
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        // Create a test product (without saving to database for testing)
        $productData = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => (float) $request->price,
            'category' => $request->category,
            'stock_quantity' => (int) $request->stock_quantity,
            'sku' => 'TEST-' . time() . '-' . Str::random(6),
            'status' => 'active',
            'created_at' => now(),
        ];

        // Upload images to S3
        $uploadedImages = [];
        $disk = Storage::disk('s3');

        foreach ($request->file('images') as $index => $file) {
            if ($file && $file->isValid()) {
                // Generate unique filename
                $timestamp = time();
                $randomString = Str::random(8);
                $extension = $file->getClientOriginalExtension();
                $filename = "product-test-{$timestamp}_{$randomString}.{$extension}";
                $path = "test-products/{$filename}";

                // Upload to S3 WITHOUT ACL options
                $uploaded = $disk->put($path, file_get_contents($file), [
                    'ContentType' => $file->getMimeType(),
                    'CacheControl' => 'max-age=31536000',
                    // Remove 'visibility' => 'public' - this causes the ACL error
                ]);

                if ($uploaded) {
                    $bucket = config('filesystems.disks.s3.bucket');
                    $region = config('filesystems.disks.s3.region');
                    $url = "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";

                    $uploadedImages[] = [
                        'url' => $url,
                        'path' => $path,
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()->toISOString(),
                        'is_primary' => $index === 0, // First image is primary
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Test product with images processed successfully!',
            'data' => [
                'product' => $productData,
                'images' => $uploadedImages,
                'total_images_uploaded' => count($uploadedImages),
            ]
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Test failed: ' . $e->getMessage(),
            'error_class' => get_class($e)
        ], 500);
    }
});

// Clean up test uploads (utility route)
Route::delete('/cleanup-test-uploads', function () {
    try {
        $disk = Storage::disk('s3');

        // List and delete test files
        $testFiles = $disk->files('test-uploads');
        $productTestFiles = $disk->files('test-products');
        $allTestFiles = array_merge($testFiles, $productTestFiles);

        $deletedCount = 0;
        foreach ($allTestFiles as $file) {
            if ($disk->delete($file)) {
                $deletedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} test files from S3",
            'deleted_files' => $allTestFiles
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Cleanup failed: ' . $e->getMessage()
        ], 500);
    }
});
