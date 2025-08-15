<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
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
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
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
                'password' => ['required', 'string', Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()],
            ]);

            $credentials = request(['email', 'password']);

            // Attempt to authenticate the user with the provided credentials
            // If authentication fails, return an error response
            // If authentication is successful, create a new bearer token for the user

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                ], 'Authentication Failed', 401);
            }

            $user = User::where('email', $request->email)->first();


            // check password & decrypt it
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'User successfully logged in');
        } catch (\Exception $e) {

            Log::error('Login failed: ' . $e->getMessage());

            return ResponseFormatter::error([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 'Authentication Failed', 401);
        }
    }

    public function updateProfile(Request $request)
    {
        // Validate input fields
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:15', Rule::unique('users')->ignore(Auth::id())],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
        ]);

        // Get current the authenticated user
        $user = Auth::user();

        // Update user
        $user->update($validateData);

        return ResponseFormatter::success(
            $user,
            'Profile Updated'
        );
    }

    // Fetch the authenticated user's data
    // automatically check request user login or not using sanctum
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile user berhasil diambil');
    }

    public function logout(Request $request) 
    {
        try {
            // Revoke the user's token
           $token = $request->user()->currentAccessToken()->delete();

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
