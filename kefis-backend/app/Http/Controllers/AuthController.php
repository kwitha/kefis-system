<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return response()->json(['message' => 'Your account is inactive.'], 403);
        }

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'branch_id' => $user->branch_id,
            ],
        ]);
    }

    // Get authenticated user
    public function me()
    {
        $user = Auth::guard('api')->user()->load('branch');
        return response()->json($user);
    }

    // Logout
    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh token
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user()->load('branch');

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'branch'    => $user->branch,
                'is_active' => $user->is_active,
            ],
        ]);
    }
}