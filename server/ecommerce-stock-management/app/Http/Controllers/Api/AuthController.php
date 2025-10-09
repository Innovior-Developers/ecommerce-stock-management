<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        // Clear old tokens
        $user->tokens()->delete();

        // Create new tokens with longer expiry
        $accessToken = $user->createToken('admin-token', ['admin']);
        $refreshToken = $user->createToken('admin-refresh-token', ['refresh']);

        return response()->json([
            'success' => true,
            'message' => 'Admin login successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400, // 24 hours
        ]);
    }

    // Add token refresh endpoint
    public function refreshToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid refresh token',
            ], 401);
        }

        // Delete current tokens
        $user->tokens()->delete();

        // Create new tokens
        $accessToken = $user->createToken(
            $user->role . '-token',
            $user->role === 'admin' ? ['admin'] : ['customer']
        );
        $refreshToken = $user->createToken(
            $user->role . '-refresh-token',
            ['refresh']
        );

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
        ]);
    }

    public function customerRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Customer::create([
            'user_id' => (string) $user->_id,
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
            'phone' => $validated['phone'] ?? null,
            'marketing_consent' => false,
        ]);

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function customerLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->where('role', 'customer')->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}