<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\QuerySanitizer;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = QuerySanitizer::sanitizeSearch($request->get('search'));

            $query = Customer::with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            $customers = $query->orderBy('created_at', 'desc')->get();

            $customersData = $customers->map(function ($customer) {
                return [
                    '_id' => $customer->_id,
                    'id' => $customer->_id,
                    'user_id' => $customer->user_id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                    'date_of_birth' => $customer->date_of_birth,
                    'gender' => $customer->gender,
                    'addresses' => $customer->addresses,
                    'preferences' => $customer->preferences,
                    'marketing_consent' => $customer->marketing_consent,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'user' => $customer->user ? [
                        '_id' => $customer->user->_id,
                        'id' => $customer->user->_id,
                        'name' => $customer->user->name,
                        'email' => $customer->user->email,
                        'role' => $customer->user->role,
                        'status' => $customer->user->status,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $customersData,
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
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID format'
                ], 400);
            }

            $customer = Customer::with(['user', 'orders'])
                ->where('_id', $sanitizedId)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    '_id' => $customer->_id,
                    'id' => $customer->_id,
                    'user_id' => $customer->user_id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                    'date_of_birth' => $customer->date_of_birth,
                    'gender' => $customer->gender,
                    'addresses' => $customer->addresses,
                    'preferences' => $customer->preferences,
                    'marketing_consent' => $customer->marketing_consent,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'user' => $customer->user ? [
                        '_id' => $customer->user->_id,
                        'id' => $customer->user->_id,
                        'name' => $customer->user->name,
                        'email' => $customer->user->email,
                    ] : null,
                    'orders_count' => $customer->orders ? $customer->orders->count() : 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID format'
                ], 400);
            }

            $customer = Customer::where('_id', $sanitizedId)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'addresses' => 'nullable|array',
                'preferences' => 'nullable|array',
                'marketing_consent' => 'boolean',
            ]);

            if (isset($validated['first_name'])) {
                $validated['first_name'] = QuerySanitizer::sanitize($validated['first_name']);
            }
            if (isset($validated['last_name'])) {
                $validated['last_name'] = QuerySanitizer::sanitize($validated['last_name']);
            }
            if (isset($validated['phone'])) {
                $validated['phone'] = QuerySanitizer::sanitize($validated['phone']);
            }

            $customer->update($validated);

            Cache::forget("customer_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => [
                    '_id' => $customer->_id,
                    'id' => $customer->_id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                    'updated_at' => $customer->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $userId = QuerySanitizer::sanitizeMongoId($user->_id);
            $customer = Customer::where('user_id', $userId)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer profile not found',
                ], 404);
            }

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'addresses' => 'nullable|array',
                'preferences' => 'nullable|array',
                'marketing_consent' => 'boolean',
            ]);

            if (isset($validated['first_name'])) {
                $validated['first_name'] = QuerySanitizer::sanitize($validated['first_name']);
            }
            if (isset($validated['last_name'])) {
                $validated['last_name'] = QuerySanitizer::sanitize($validated['last_name']);
            }
            if (isset($validated['phone'])) {
                $validated['phone'] = QuerySanitizer::sanitize($validated['phone']);
            }

            $customer->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    '_id' => $customer->_id,
                    'id' => $customer->_id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'phone' => $customer->phone,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sanitizedId = QuerySanitizer::sanitizeMongoId($id);

            if (!$sanitizedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID format'
                ], 400);
            }

            $customer = Customer::where('_id', $sanitizedId)->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $orderCount = $customer->orders()->count();
            if ($orderCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete customer with {$orderCount} existing orders"
                ], 400);
            }

            if ($customer->user) {
                $customer->user->delete();
            }

            $customer->delete();

            Cache::forget("customer_{$sanitizedId}");
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer'
            ], 500);
        }
    }
}