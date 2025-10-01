<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        Log::info('=== PRODUCT CREATION REQUEST START ===');
        Log::info('Content-Type: ' . $request->header('Content-Type'));
        Log::info('All request data (except files):', $request->except(array_keys($request->allFiles())));
        Log::info('All files received by Laravel:', array_keys($request->allFiles()));

        // IMPROVED: Gather image files with better detection
        $imageFiles = [];
        $allFiles = $request->allFiles();

        // Check for 'images' (plural) key
        if (isset($allFiles['images'])) {
            $imageFiles = is_array($allFiles['images']) ? $allFiles['images'] : [$allFiles['images']];
            Log::info('Found ' . count($imageFiles) . ' files under "images" key');
        }
        // Check for 'image' (singular) key as fallback
        else if (isset($allFiles['image'])) {
            $imageFiles = is_array($allFiles['image']) ? $allFiles['image'] : [$allFiles['image']];
            Log::info('Found ' . count($imageFiles) . ' files under "image" key');
        }
        // Look for any indexed keys like images[0], image[0]
        else {
            foreach ($allFiles as $key => $value) {
                if (strpos($key, 'image') === 0) {
                    $imageFiles[] = $value;
                    Log::info('Found file with key: ' . $key);
                }
            }
        }

        if (empty($imageFiles)) {
            Log::warning('No image files detected in the request');
        } else {
            Log::info('Total image files found: ' . count($imageFiles));
        }

        // Validate text fields first
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
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the product
        $validatedData = $validator->validated();
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'active';
        }

        $product = new Product($validatedData);
        $product->save();

        Log::info('Product created with ID: ' . $product->_id);

        // Process image files if they exist
        if (!empty($imageFiles)) {
            Log::info('Attempting to upload ' . count($imageFiles) . ' images');
            $uploadedImages = $product->uploadMultipleImages($imageFiles);

            if (!empty($uploadedImages)) {
                Log::info('Successfully uploaded ' . count($uploadedImages) . ' images');
                $product->images = $uploadedImages;
                $product->save();
                Log::info('Saved image URLs to product');
            } else {
                Log::error('Failed to upload images');
            }
        }

        // Clear cache and return response
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->fresh()
        ], 201);
    }

    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(serialize($request->all()));

        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Product::query();

            // Search filter
            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($category = $request->get('category')) {
                $query->where('category', $category);
            }

            // Status filter
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            return $query->latest()->paginate($request->get('per_page', 20));
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Cache::remember("product_{$id}", 600, function () use ($id) {
            return Product::findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $product->fill($validatedData);

        // Handle new images
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }

            $existingImages = $product->images ?? [];
            $newImages = $product->uploadMultipleImages($files, $existingImages);
            $product->images = $newImages;
        }

        $product->save();

        // Clear caches
        Cache::forget("product_{$product->_id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh()
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete all images from S3
        $product->deleteImages();

        $product->delete();

        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    // Upload additional images to existing product
    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $existingImages = $product->images ?? [];
        $newImages = $product->uploadMultipleImages($request->file('images'), $existingImages);

        $product->update(['images' => $newImages]);

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $product->fresh(),
        ]);
    }

    // Delete specific image
    public function deleteImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);

        $product->deleteImage($request->input('image_index'));

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
            'data' => $product->fresh(),
        ]);
    }
}
