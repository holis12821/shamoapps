<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CheckoutController;
use App\Http\Controllers\API\ProductCategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\RefreshTokenController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth API
|--------------------------------------------------------------------------
*/

Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register')->middleware('throttle:5,1');
        Route::post('/login', 'login')->middleware('throttle:5,1');
        Route::post('/logout', 'logout')
            ->middleware(['auth:sanctum', 'abilities:private']);
    });

Route::middleware(['auth:sanctum', 'throttle:10,1'])
    ->post('/refresh', RefreshTokenController::class);

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:60,1')->group(function () {
    Route::get('products', [ProductController::class, 'all']);
    Route::get('categories', [ProductCategoryController::class, 'all']);
});

/*
|--------------------------------------------------------------------------
| Private API
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'abilities:private'])->group(function () {
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('user', [UserController::class, 'updateProfile']);
});


Route::prefix('order')->middleware([
    'auth:sanctum',
    'abilities:private',
    'resolvecart',
    'requirecartsecret'
])->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
});

Route::prefix('transactions')->middleware([
    'auth:sanctum',
    'abilities:private',
])->group(function () {
    Route::get('/', [TransactionController::class, 'all']);
});

/*
|--------------------------------------------------------------------------
| Cart API
|--------------------------------------------------------------------------
*/
/**
 * Create cart (anonymous)
 * No middleware
 */

Route::prefix('cart')->group(function () {
    Route::post('/', [CartController::class, 'create'])
        ->middleware('throttle:30,1');

    /**
     * Read cart
     * Header: X-CART-ID
     */
    Route::middleware('resolvecart')->get('/', [CartController::class, 'show']);

    /**
     * Cart item operations
     * Header: X-CART-ID
     */

    Route::middleware('resolvecart')->group(function () {
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/items/{item}', [CartController::class, 'removeItem']);
    });

    /**
     * Sensitive operations
     * Header:
     * - X-CART-ID
     * - X-CART-SECRET
     */
    Route::middleware([
        'auth:sanctum',
        'abilities:private',
        'resolvecart',
        'requirecartsecret'
    ])->group(function () {
        Route::post('/claim', [CartController::class, 'claim']);
    });
});
