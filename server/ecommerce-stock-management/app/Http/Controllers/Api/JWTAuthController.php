<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class JWTAuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Not an admin user',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account inactive',
            ], 403);
        }

        $token = JWTAuth::fromUser($user);
        return $this->respondWithToken($token, $user, 'Admin login successful');
    }

    public function customerLogin(Request $request)
    {
        Log::info('=== Customer Login Debug ===');
        Log::info('Request data: ', $request->all());

        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        Log::info('Looking for user with email: ' . $credentials['email']);

        $user = User::where('email', $credentials['email'])
            ->where('role', 'customer')
            ->first();

        if (!$user) {
            Log::warning('User not found: ' . $credentials['email']);
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        Log::info('User found: ' . $user->email . ', status: ' . $user->status);

        if (!Hash::check($credentials['password'], $user->password)) {
            Log::warning('Password check failed for: ' . $credentials['email']);
            throw ValidationException::withMessages([
                'email' => ['Invalid password.'],
            ]);
        }

        if ($user->status !== 'active') {
            Log::warning('User inactive: ' . $credentials['email']);
            return response()->json([
                'success' => false,
                'message' => 'Account inactive',
            ], 403);
        }

        try {
            $token = JWTAuth::fromUser($user);
            Log::info('Token generated successfully for: ' . $user->email);

            return $this->respondWithToken($token, $user, 'Customer login successful');
        } catch (\Exception $e) {
            Log::error('Token generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function customerRegister(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role'     => 'customer',
            'status'   => 'active',
        ]);

        Customer::create([
            'user_id' => $user->_id,
            'first_name' => $user->name,
        ]);

        $token = JWTAuth::fromUser($user);
        return $this->respondWithToken($token, $user, 'Registration successful', 201);
    }

    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'success' => true,
            'user' => [
                'id'    => (string)$user->_id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());
        $user = JWTAuth::setToken($newToken)->toUser();

        return $this->respondWithToken($newToken, $user, 'Token refreshed');
    }

    protected function respondWithToken($token, $user, $message, $statusCode = 200)
    {
        $ttlMinutes = config('jwt.ttl', 60);

        return response()->json([
            'success'       => true,
            'message'       => $message,
            'token'         => $token,
            'token_type'    => 'Bearer',
            'expires_in'    => $ttlMinutes * 60,  // ✅ Fixed: was "expires'_in'"
            'user' => [
                'id'     => (string)$user->_id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ],
        ], $statusCode);
    }
}
