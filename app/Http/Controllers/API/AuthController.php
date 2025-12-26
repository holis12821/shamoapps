<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        // Validate the incoming request data
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'phone' => ['required', 'string', 'max:15'],
                'password' => ['required', 'string', Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()],
            ]);
            // Create a new user instance and save it to the database
            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // get data base on email
            $user = User::where('email', $request->email)->first();

            // Create a new bearer token for the user
            $accessToken = TokenService::createToken(
                user: $user,
                name: 'access',
                abilities: ['private'],
                request: $request,
                days: 1
            );

            $refreshToken = TokenService::createToken(
                user: $user,
                name: 'refresh',
                abilities: ['refresh'],
                request: $request,
                days: 30
            );

            return ResponseFormatter::success([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'User successfully registered');
        } catch (\Throwable $e) {
            // Handle any exceptions that occur during registration
            return ResponseFormatter::error([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 'Authentication Failed', 401);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => [
                    'required',
                    'string',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
            ]);

            $credentials = $request->only('email', 'password');

            // Attempt to authenticate the user with the provided credentials
            // If authentication fails, return an error response
            // If authentication is successful, create a new bearer token for the user

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Invalid credentials',
                ], 'Authentication Failed', 401);
            }

            /** @var User $user */
            $user = Auth::user();

            // Create ACCESS token (short-lived)
            $accessToken = TokenService::createToken(
                user: $user,
                name: 'access',
                abilities: ['private'],
                request: $request,
                days: 1
            );

            $refreshToken = TokenService::createToken(
                user: $user,
                name: 'refresh',
                abilities: ['refresh'],
                request: $request,
                days: 30
            );

            return ResponseFormatter::success([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'User successfully logged in');
        } catch (\Exception $e) {

            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null,
            ]);

            return ResponseFormatter::error([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 'Authentication Failed', 401);
        }
    }

    public function refresh(Request $request)
    {
        try {
            /** @var User $user */
            $user = $request->user();

            // Revoke current refresh token
            $request->user()->currentAccessToken()->delete();

            // Create new ACCESS token (short-lived)
            $newAccessToken = TokenService::createToken(
                user: $user,
                name: 'access',
                abilities: ['private'],
                request: $request,
                days: 1
            );

            return ResponseFormatter::success([
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
            ], 'Token refreshed');
        } catch (Exception $e) {
            Log::error('Refresh token failed', [
                'error' => $e->getMessage(),
            ]);

            return ResponseFormatter::error([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 'Authentication Failed', 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Revoke the user's token
            $token = $request->user()->tokens()->delete();

            return ResponseFormatter::success($token, 'User successfully logged out');
        } catch (Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return ResponseFormatter::error([
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 'Authentication Failed', 401);
        }
    }
}
