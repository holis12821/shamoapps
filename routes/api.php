<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductCategoryController;
use App\Http\Controllers\API\ProductController;
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
        Route::post('register', 'register')->middleware('throttle:5,1');
        Route::post('login', 'login')->middleware('throttle:5,1');

        Route::post('refresh', 'refresh')
            ->middleware(['auth:sanctum', 'abilities:refresh', 'throttle:10,1']);

        Route::post('logout', 'logout')
            ->middleware(['auth:sanctum', 'abilities:private']);
});

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

    Route::get('transactions', [TransactionController::class, 'all']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
});