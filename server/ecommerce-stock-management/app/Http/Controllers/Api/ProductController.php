<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\QuerySanitizer;
use App\Services\ImageValidator; // ✅ ADD THIS

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query();

            // ✅ SANITIZE search input
            if ($request->has('search')) {
                $search = QuerySanitizer::sanitizeSearch($request->get('search'));

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
                }
            }

            // ✅ SANITIZE category filter
            if ($request->has('category')) {
                $category = QuerySanitizer::sanitize($request->get('category'));
                if ($category) {
                    $query->where('category', $category);
                }
            }

            $products = $query->get();

            // ✅ Ensure consistent ID format
            $products = $products->map(function ($product) {
                return [
                    '_id' => $product->_id,
                    'id' => $product->_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'category' => $product->category,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => $product->status,
                    'sku' => $product->sku,
                    'images' => $product->images,
                    'weight' => $product->weight,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    '_id' => $product->_id,
                    'id' => $product->_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'category' => $product->category,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => $product->status,
                    'sku' => $product->sku,
                    'images' => $product->images,
                    'weight' => $product->weight,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('=== PRODUCT CREATION REQUEST START ===');

        // Gather image files
        $imageFiles = [];
        $allFiles = $request->allFiles();

        if (isset($allFiles['images'])) {
            $imageFiles = is_array($allFiles['images']) ? $allFiles['images'] : [$allFiles['images']];
        } else if (isset($allFiles['image'])) {
            $imageFiles = is_array($allFiles['image']) ? $allFiles['image'] : [$allFiles['image']];
        }

        // ✅ VALIDATE images FIRST (before text validation)
        if (!empty($imageFiles)) {
            foreach ($imageFiles as $index => $file) {
                $validation = ImageValidator::validate($file);

                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Image #{$index}: " . $validation['error'],
                        'error_code' => 'INVALID_IMAGE'
                    ], 422);
                }
            }
        }

        // Validate text fields
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'stock_quantity' => 'required|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
            'weight' => 'nullable|numeric|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // ✅ SANITIZE string inputs
        $validatedData['name'] = QuerySanitizer::sanitize($validatedData['name']);
        $validatedData['description'] = QuerySanitizer::sanitize($validatedData['description']);
        $validatedData['category'] = QuerySanitizer::sanitize($validatedData['category']);

        if (isset($validatedData['meta_title'])) {
            $validatedData['meta_title'] = QuerySanitizer::sanitize($validatedData['meta_title']);
        }
        if (isset($validatedData['meta_description'])) {
            $validatedData['meta_description'] = QuerySanitizer::sanitize($validatedData['meta_description']);
        }

        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'active';
        }

        $product = new Product($validatedData);
        $product->save();

        Log::info('Product created with ID: ' . $product->_id);

        // Process images
        if (!empty($imageFiles)) {
            Log::info('Uploading ' . count($imageFiles) . ' images');
            $uploadedImages = $product->uploadMultipleImages($imageFiles);

            if (!empty($uploadedImages)) {
                $product->images = $uploadedImages;
                $product->save();
                Log::info('Saved ' . count($uploadedImages) . ' images');
            }
        }

        Cache::forget('products_list');
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->fresh()
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // ✅ ADD: Log incoming request
            Log::info('=== PRODUCT UPDATE REQUEST START ===');
            Log::info('Product ID: ' . $sanitizedId);
            Log::info('Request has files: ' . ($request->hasFile('images') ? 'YES' : 'NO'));
            Log::info('All files: ', $request->allFiles());
            Log::info('Request data: ', $request->except(['images']));

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category' => 'sometimes|required|string',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'status' => 'sometimes|required|in:active,inactive',
                'weight' => 'nullable|numeric|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'existing_images' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // ✅ SANITIZE string inputs
            if (isset($validated['name'])) {
                $validated['name'] = QuerySanitizer::sanitize($validated['name']);
            }
            if (isset($validated['description'])) {
                $validated['description'] = QuerySanitizer::sanitize($validated['description']);
            }
            if (isset($validated['category'])) {
                $validated['category'] = QuerySanitizer::sanitize($validated['category']);
            }
            if (isset($validated['meta_title'])) {
                $validated['meta_title'] = QuerySanitizer::sanitize($validated['meta_title']);
            }
            if (isset($validated['meta_description'])) {
                $validated['meta_description'] = QuerySanitizer::sanitize($validated['meta_description']);
            }

            // ✅ FIX: Handle existing images FIRST
            if ($request->has('existing_images')) {
                $existingImages = json_decode($request->input('existing_images'), true);
                Log::info('Existing images from request:', ['count' => count($existingImages ?? [])]);
                $product->images = $existingImages ?? [];
            }

            // Remove existing_images from validated data
            unset($validated['existing_images']);

            // Update product fields
            $product->fill($validated);

            // ✅ FIX: Handle NEW images properly
            if ($request->hasFile('images')) {
                $files = $request->file('images');

                // Ensure it's an array
                if (!is_array($files)) {
                    $files = [$files];
                }

                Log::info('Processing ' . count($files) . ' new images for update');

                // ✅ VALIDATE each image BEFORE upload
                foreach ($files as $index => $file) {
                    $validation = ImageValidator::validate($file);
                    if (!$validation['valid']) {
                        Log::error("Image validation failed at index {$index}: " . $validation['error']);
                        return response()->json([
                            'success' => false,
                            'message' => "Image #{$index}: " . $validation['error'],
                            'error_code' => 'INVALID_IMAGE'
                        ], 422);
                    }
                }

                // Get current images (either from existing_images or current DB state)
                $currentImages = $product->images ?? [];
                Log::info('Current images before upload:', ['count' => count($currentImages)]);

                // Upload new images and merge with existing
                $newImages = $product->uploadMultipleImages($files, $currentImages);

                Log::info('Images after upload:', ['count' => count($newImages)]);

                // ✅ CRITICAL: Set the images on the product
                $product->images = $newImages;
            }

            // ✅ SAVE the product
            $product->save();

            Log::info('Product updated successfully');
            Log::info('Final image count: ' . count($product->images ?? []));
            Log::info('=== PRODUCT UPDATE REQUEST END ===');

            // Clear cache
            Cache::forget("product_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Delete images from S3
            $product->deleteImages();

            $product->delete();

            Cache::forget("product_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product'
            ], 500);
        }
    }

    public function uploadImages(Request $request, $id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // ✅ UPDATED: Use ImageValidator constants
            $request->validate([
                'images' => 'required|array|max:5',
                'images.*' => [
                    'required',
                    'file',
                    'mimes:' . implode(',', ImageValidator::getAllowedExtensions()),
                    'max:' . (ImageValidator::getMaxFileSize() / 1024), // Laravel expects KB
                ],
            ]);

            // ✅ VALIDATE each image
            foreach ($request->file('images') as $index => $file) {
                $validation = ImageValidator::validate($file);

                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Image #{$index}: " . $validation['error']
                    ], 422);
                }
            }

            $existingImages = $product->images ?? [];
            $newImages = $product->uploadMultipleImages($request->file('images'), $existingImages);

            $product->update(['images' => $newImages]);

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $product->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading images: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request, $id)
    {
        try {
            // ✅ VALIDATE and SANITIZE ID
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], 400);
            }

            $product = Product::where('_id', $sanitizedId)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $request->validate([
                'image_index' => 'required|integer|min:0',
            ]);

            $product->deleteImage($request->input('image_index'));

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
                'data' => $product->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }
}
