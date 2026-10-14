<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerAuthController;
use Illuminate\Support\Facades\Route;

// Customer Account and Cart modules use the separate customer guard.
Route::name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::get('/login/customer', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login/customer', [CustomerAuthController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('logout');
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('/cart/submit', [CartController::class, 'processCheckout'])->name('cart.submit');
        Route::get('/orders', [CartController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{cart}', [CartController::class, 'showOrder'])->name('orders.show');
    });
});
