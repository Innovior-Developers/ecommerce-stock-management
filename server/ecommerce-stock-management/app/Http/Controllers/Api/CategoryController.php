<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');

            $query = Category::query();

            if ($search) {
                $query->where('name', 'regex', "/$search/i")
                    ->orWhere('description', 'regex', "/$search/i");
            }

            $categories = $query->latest()->get()->map(function ($category) {
                $category->products_count = \App\Models\Product::where('category', $category->name)->count();
                return $category;
            });

            return CategoryResource::collection($categories)
                ->additional([
                    'success' => true,
                    'message' => 'Categories retrieved successfully'
                ]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Creating category', ['data' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|string',
                'image_url' => 'nullable|url',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'status' => 'in:active,inactive',
                'sort_order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            $validated = $validator->validated();

            // Generate slug from name
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Set defaults
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }
            if (!isset($validated['sort_order'])) {
                $validated['sort_order'] = 0;
            }

            $category = Category::create($validated);

            // ✅ FIX: Remove cache tags (only works with Redis)
            Cache::flush(); // Clear all cache instead

            Log::info('Category created successfully', ['id' => $category->_id]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = $this->findByHashedId($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $category->products_count = \App\Models\Product::where('category', $category->name)->count();

            return (new CategoryResource($category))
                ->additional([
                    'success' => true,
                    'message' => 'Category retrieved successfully'
                ]);
        } catch (\Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category'
            ], 500);
        }
    }

    private function findByHashedId($hashedId)
    {
        $hash = str_replace('cat_', '', $hashedId);

        $categories = Category::all();
        foreach ($categories as $category) {
            $categoryHash = substr(hash('sha256', (string)$category->_id), 0, 16);
            if ($categoryHash === $hash) {
                return $category;
            }
        }

        return null;
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Updating category', ['id' => $id, 'data' => $request->all()]);

            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255|unique:categories,name,' . $id . ',_id',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|string',
                'image_url' => 'nullable|url',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'status' => 'in:active,inactive',
                'sort_order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }

            $validated = $validator->validated();

            // Update slug if name changed
            if (isset($validated['name']) && $validated['name'] !== $category->name) {
                $slug = Str::slug($validated['name']);

                // Ensure unique slug
                $originalSlug = $slug;
                $counter = 1;
                while (Category::where('slug', $slug)->where('_id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $validated['slug'] = $slug;

                // Update all products with old category name to new name
                \App\Models\Product::where('category', $category->name)
                    ->update(['category' => $validated['name']]);
            }

            $category->update($validated);

            // ✅ FIX: Remove cache tags
            Cache::forget("category_{$id}");
            Cache::flush();

            Log::info('Category updated successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info('Deleting category', ['id' => $id]);

            $category = Category::findOrFail($id);

            // Check if category has children
            $hasChildren = Category::where('parent_id', $id)->exists();
            if ($hasChildren) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with subcategories. Please delete or reassign subcategories first.',
                ], 400);
            }

            // Check if category has products
            $productCount = \App\Models\Product::where('category', $category->name)->count();
            if ($productCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category with {$productCount} products. Please reassign or delete products first.",
                ], 400);
            }

            $category->delete();

            // ✅ FIX: Remove cache tags
            Cache::forget("category_{$id}");
            Cache::flush();

            Log::info('Category deleted successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }

    // Additional helper method to get category tree
    public function tree()
    {
        try {
            $categories = Category::where('status', 'active')
                ->orderBy('sort_order')
                ->get();

            // Build tree structure
            $tree = $this->buildTree($categories);

            return response()->json([
                'success' => true,
                'data' => $tree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category tree'
            ], 500);
        }
    }

    private function buildTree($categories, $parentId = null)
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->_id);
                $categoryArray = $category->toArray();

                if ($children) {
                    $categoryArray['children'] = $children;
                }

                $branch[] = $categoryArray;
            }
        }

        return $branch;
    }
}
