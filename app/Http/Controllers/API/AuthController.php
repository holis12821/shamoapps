<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\TokenService;
use App\Services\CartService;
use App\Services\CartMergeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        // Validate the incoming request data
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
        $accessToken = TokenService::createAccessToken(
            user: $user,
            request: $request,
        );

        $refreshToken = TokenService::createRefreshToken(user: $user);

        return ResponseFormatter::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'User successfully registered');
    }

    public function login(Request $request)
    {

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
           throw new ApiException('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        /**
         * CLAIM GUEST CART (IF EXISTS)
         * - Guest addToCart → login → cart remains
         * - Login without cart → do not create new cart
         * - User has old cart → merge
         */
        $cartService = app(CartService::class);
        $cartMergeService = app(CartMergeService::class);

        $guestCart = $cartService->getGuestCart();

        if ($guestCart && $guestCart->items()->exists()) {
            $cartMergeService->claim($guestCart, $user);
        }

        // Create ACCESS token (short-lived)
        $accessToken = TokenService::createAccessToken(
            user: $user,
            request: $request,
        );

        $refreshToken = TokenService::createRefreshToken(user: $user);

        return ResponseFormatter::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'User successfully logged in');
    }

    public function logout(Request $request)
    {
        // Revoke the user's token
        $token = $request->user()->tokens()->delete();

        return ResponseFormatter::success($token, 'User successfully logged out');
    }
}
