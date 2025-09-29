<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // ✅ Add this import
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_' . md5(serialize($request->all()));

        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Product::query();

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'regex', "/$search/i")
                        ->orWhere('description', 'regex', "/$search/i")
                        ->orWhere('sku', 'regex', "/$search/i");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                $query->where('status', 'active'); // Default to active products
            }

            // Stock filter
            if ($request->has('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            return $query->paginate($request->get('per_page', 20));
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        // ✅ Enhanced debug logging
        Log::info('=== Product Creation Debug ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Content type: ' . $request->header('content-type'));
        Log::info('All data: ', $request->all());
        Log::info('All files: ', $request->allFiles());
        Log::info('Has images file: ' . ($request->hasFile('images') ? 'YES' : 'NO'));

        // Check for both array format and individual files
        $hasImagesArray = $request->hasFile('images');
        $hasImageFiles = false;

        // Check for images[0], images[1], etc.
        for ($i = 0; $i < 5; $i++) {
            if ($request->hasFile("images.{$i}")) {
                $hasImageFiles = true;
                Log::info("Found image file at index {$i}");
                break;
            }
        }

        Log::info("Images array format: " . ($hasImagesArray ? 'YES' : 'NO'));
        Log::info("Images individual format: " . ($hasImageFiles ? 'YES' : 'NO'));

        // ✅ Updated validation - handle both formats
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'category' => 'required|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            // ✅ Handle both formats
            'images' => 'nullable|array|max:5',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        // Set defaults
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;

        // ✅ Get images from both possible formats
        $imageFiles = [];

        // Try array format first
        if ($request->hasFile('images') && is_array($request->file('images'))) {
            $imageFiles = $request->file('images');
            Log::info('Using array format images: ' . count($imageFiles));
        } else {
            // Try individual format
            for ($i = 0; $i < 5; $i++) {
                if ($request->hasFile("images.{$i}")) {
                    $imageFiles[] = $request->file("images.{$i}");
                    Log::info("Added individual image at index {$i}");
                }
            }
        }

        // Remove images from validated data
        unset($validated['images']);

        Log::info('Final image files count: ' . count($imageFiles));

        // Create product first
        $product = Product::create($validated);

        // ✅ Upload images if any found
        if (!empty($imageFiles)) {
            Log::info('Starting image upload for product: ' . $product->_id);

            try {
                $uploadedImages = $product->uploadMultipleImages($imageFiles);

                if (!empty($uploadedImages)) {
                    $product->update(['images' => $uploadedImages]);
                    Log::info('Images uploaded successfully', [
                        'count' => count($uploadedImages),
                        'images' => $uploadedImages
                    ]);
                } else {
                    Log::warning('Image upload returned empty array');
                }
            } catch (\Exception $e) {
                Log::error('Image upload failed: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
            }
        } else {
            Log::warning('No image files found to upload');
        }

        // Return fresh product with images
        $freshProduct = $product->fresh();
        Log::info('Final product data: ', $freshProduct->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $freshProduct,
        ], 201);
    }

    public function show($id)
    {
        $product = Cache::remember("product_{$id}", 600, function () use ($id) {
            return Product::with('inventory')->findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'sku' => ['nullable', 'string', Rule::unique('products')->ignore($id, '_id')],
            'category' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'new_images' => 'nullable|array|max:5',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_images' => 'nullable|array', // Array of image indices to remove
            'remove_images.*' => 'integer',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        // Handle image removals
        if ($request->has('remove_images')) {
            $removeImages = $request->input('remove_images');
            // Sort in descending order to avoid index issues
            rsort($removeImages);

            foreach ($removeImages as $imageIndex) {
                $product->deleteImage($imageIndex);
            }

            // Refresh product data
            $product = $product->fresh();
        }

        // Handle new images
        $newImages = $request->file('new_images', []);
        if (!empty($newImages)) {
            $existingImages = $product->images ?? [];
            $updatedImages = $product->uploadMultipleImages($newImages, $existingImages);
            $validated['images'] = $updatedImages;
        }

        // Remove image-related fields from validated data
        unset($validated['new_images'], $validated['remove_images']);

        $product->update($validated);

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Check if product has orders
        $hasOrders = \App\Models\Order::where('items.product_id', $id)->exists();
        if ($hasOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product that has been ordered',
            ], 400);
        }

        $product->delete(); // This will trigger deleteImages() in the model

        // Clear cache
        Cache::forget("product_{$id}");
        Cache::tags(['products'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    // Additional endpoint to upload images separately
    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
