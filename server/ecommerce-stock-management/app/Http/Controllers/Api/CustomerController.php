<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'customers_' . md5(serialize($request->all()));

        $customers = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Customer::with('user');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'regex', "/$search/i")
                        ->orWhere('last_name', 'regex', "/$search/i");
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('email', 'regex', "/$search/i");
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate(20);
        });

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function show($id)
    {
        $customer = Cache::remember("customer_{$id}", 300, function () use ($id) {
            return Customer::with(['user', 'orders'])->findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'addresses' => 'nullable|array',
            'preferences' => 'nullable|array',
            'marketing_consent' => 'boolean',
        ]);

        $customer->update($validated);

        // Clear cache
        Cache::forget("customer_{$id}");
        Cache::tags(['customers'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'addresses' => 'nullable|array',
            'preferences' => 'nullable|array',
            'marketing_consent' => 'boolean',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $customer,
        ]);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with existing orders',
            ], 400);
        }

        // Also delete associated user
        if ($customer->user) {
            $customer->user->delete();
        }

        $customer->delete();

        Cache::forget("customer_{$id}");
        Cache::tags(['customers'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }
}