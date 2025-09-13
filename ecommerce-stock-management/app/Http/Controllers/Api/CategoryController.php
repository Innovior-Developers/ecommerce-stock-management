<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'categories_' . md5(serialize($request->all()));

        $categories = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Category::query();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            return $query->orderBy('sort_order', 'asc')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|string',
            'image_url' => 'nullable|url',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'in:active,inactive',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = Category::create($validated);

        // Clear cache
        Cache::tags(['categories'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show($id)
    {
        $category = Cache::remember("category_{$id}", 600, function () use ($id) {
            return Category::with('children', 'parent')->findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|string',
            'image_url' => 'nullable|url',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'in:active,inactive',
            'sort_order' => 'integer|min:0',
        ]);

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
        }

        $category->update($validated);

        // Clear cache
        Cache::forget("category_{$id}");
        Cache::tags(['categories'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories',
            ], 400);
        }

        // Check if category has products
        $productCount = \App\Models\Product::where('category', $category->name)->count();
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with products',
            ], 400);
        }

        $category->delete();

        // Clear cache
        Cache::forget("category_{$id}");
        Cache::tags(['categories'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
