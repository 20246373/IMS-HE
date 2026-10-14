<?php

use App\Http\Controllers\PosController;
use App\Http\Controllers\SaleController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Sales Management: Admin, Store Supervisor and Employee
Route::middleware('role:'.User::ADMIN.','.User::STORE_SUPERVISOR.','.User::EMPLOYEE)->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/cart/{cartId}/process', [PosController::class, 'processSubmittedCart'])->name('pos.process.cart');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{invoice}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{invoice}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
});

// Online Order Processing: Admin and Store Supervisor
Route::middleware('role:'.User::ADMIN.','.User::STORE_SUPERVISOR)->group(function () {
    Route::get('/sales/online', [SaleController::class, 'onlineOrders'])->name('sales.online');
    Route::post('/sales/{invoice}/process', [SaleController::class, 'processOnlineOrder'])->name('sales.process');
});
