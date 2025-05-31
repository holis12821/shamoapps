<?php

use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\ProductCategoryController;
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
        Route::resource('category', ProductCategoryController::class);
    });
});
