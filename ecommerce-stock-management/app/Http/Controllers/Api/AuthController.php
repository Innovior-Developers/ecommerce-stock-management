<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($data)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var User $user */
        $user = User::where('email', $data['email'])->first();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Issue a personal access token with admin scope
        $tokenResult = $user->createToken('admin-token', ['admin']);
        $accessToken = $tokenResult->accessToken;
        $expiresAt = $tokenResult->token->expires_at ?? now()->addHour();

        return response()->json([
            'success' => true,
            'message' => 'Admin login successful',
            'user' => [
                'id' => (string)($user->id ?? $user->_id ?? ''),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'admin',
            ],
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
        ]);
    }
}
