<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Client;

class OAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Get OAuth token
        $tokenResponse = $this->getOAuthToken($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenResponse['expires_in'],
        ], 201);
    }

    public function login(Request $request)
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

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        // Get OAuth token
        $tokenResponse = $this->getOAuthToken($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenResponse['expires_in'],
        ]);
    }

    public function refreshToken(Request $request)
    {
        $validated = $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $client = Client::where('password_client', true)->first();

        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $validated['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => '',
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid refresh token',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $request->user()->_id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ],
        ]);
    }

    private function getOAuthToken($email, $password)
    {
        $client = Client::where('password_client', true)->first();

        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'email' => ['Authentication failed'],
            ]);
        }

        return $response->json();
    }
}