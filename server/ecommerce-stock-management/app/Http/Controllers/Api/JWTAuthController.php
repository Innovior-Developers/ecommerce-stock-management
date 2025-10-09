<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Services\QuerySanitizer;
use Illuminate\Support\Facades\Log;

class JWTAuthController extends Controller
{
    /**
     * Admin Login
     */
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = QuerySanitizer::sanitize($credentials['email']);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
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

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user, 'Admin login successful');
    }

    /**
     * Customer Login
     */
    public function customerLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = QuerySanitizer::sanitize($credentials['email']);

        $user = User::where('email', $email)->where('role', 'customer')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
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

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user, 'Login successful');
    }

    /**
     * Customer Register - DEFINITIVE FIX
     */
    public function customerRegister(Request $request)
    {
        Log::info('=== REGISTRATION START ===');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
            ]);

            Log::info('Validation passed');

            // ✅ BULLETPROOF FIX: Create user and immediately retrieve from DB
            $userData = [
                'name' => QuerySanitizer::sanitize($validated['name']),
                'email' => QuerySanitizer::sanitize($validated['email']),
                'password' => $validated['password'], // Mutator will hash
                'role' => 'customer',
                'status' => 'active',
                'email_verified_at' => now(),
            ];

            // Step 1: Insert the user
            User::create($userData);

            // Step 2: IMMEDIATELY retrieve the user from database
            // This ensures we have the _id that MongoDB assigned
            $user = User::where('email', $userData['email'])->firstOrFail();

            Log::info('User retrieved from database with _id:', [
                '_id' => $user->_id,
                'id_type' => gettype($user->_id),
            ]);

            // Step 3: Verify we have the _id
            if (!$user->_id) {
                Log::critical('IMPOSSIBLE: User exists in DB but _id is still null');
                throw new \Exception('Failed to retrieve user ID after creation');
            }

            // Step 4: Create customer profile
            $customer = Customer::create([
                'user_id' => (string) $user->_id,
                'first_name' => QuerySanitizer::sanitize($validated['first_name'] ?? ''),
                'last_name' => QuerySanitizer::sanitize($validated['last_name'] ?? ''),
                'phone' => QuerySanitizer::sanitize($validated['phone'] ?? ''),
                'marketing_consent' => false,
            ]);

            Log::info('Customer profile created:', ['customer_id' => $customer->_id]);

            // Step 5: Generate JWT token
            $token = JWTAuth::fromUser($user);

            Log::info('=== REGISTRATION SUCCESS ===');

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => (string) $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Registration error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the authenticated User
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                ],
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
                'error_code' => 'TOKEN_INVALID'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'error_code' => 'TOKEN_ABSENT'
            ], 401);
        }
    }

    /**
     * Logout User (Invalidate token)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout, please try again.'
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired and cannot be refreshed',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
                'error_code' => 'TOKEN_REFRESH_FAILED'
            ], 500);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token, $user, $message, $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatar,
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60 // Convert minutes to seconds
        ], $statusCode);
    }
}