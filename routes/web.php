<?php

use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGalleryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('dashboard')->as('dashboard.')->group(function () {
    Route::get('/', [DashBoardController::class, 'index'])->name('index');

    Route::middleware(['admin'])->group(function () {
        Route::resource('product', ProductController::class);
        Route::resource('category', ProductCategoryController::class);
        Route::resource('product.gallery', ProductGalleryController::class)
            ->shallow()
            ->only(['index', 'create', 'store', 'destroy']);
        Route::resource('transaction', TransactionController::class)
            ->only(['index', 'show', 'edit', 'update']);   
        Route::resource('user', UserController::class)
            ->only(['index', 'edit', 'update', 'destroy']);     
    });
});
