<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Inventory Management: Admin, Inventory Clerk (full access), Store Supervisor (update stock only)
Route::middleware('role:'.User::ADMIN.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
});

// Inventory Clerk only: create/delete products, manage categories, set reorder points
Route::middleware('role:'.User::INVENTORY_CLERK)->group(function () {
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
});
