<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');

            $query = Customer::with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'regex', "/$search/i")
                        ->orWhere('last_name', 'regex', "/$search/i")
                        ->orWhere('phone', 'regex', "/$search/i");
                });
            }

            $customers = $query->orderBy('created_at', 'desc')->paginate(20);

            return CustomerResource::collection($customers)
                ->additional([
                    'success' => true,
                    'message' => 'Customers retrieved successfully'
                ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Reverse hash to find customer by hashed ID
            $customer = $this->findByHashedId($id);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return (new CustomerResource($customer->load('user')))
                ->additional([
                    'success' => true,
                    'message' => 'Customer retrieved successfully'
                ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer'
            ], 500);
        }
    }

    private function findByHashedId($hashedId)
    {
        // Remove prefix
        $hash = str_replace('cus_', '', $hashedId);

        // Search through customers to find matching hash
        $customers = Customer::all();
        foreach ($customers as $customer) {
            $customerHash = substr(hash('sha256', (string)$customer->_id), 0, 16);
            if ($customerHash === $hash) {
                return $customer;
            }
        }

        return null;
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
 