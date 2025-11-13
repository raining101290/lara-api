<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register new user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $user = User::create($request->only('name', 'email', 'password'));

        return ApiResponse::success('User created successfully', $user, 201);
    }

    // Login user and return JWT token
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    // Get user profile
    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    // Logout user (invalidate token)
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh JWT token
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    // Return token response structure
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}