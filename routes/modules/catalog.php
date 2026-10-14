<?php

use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

// Product Catalog module: public storefront.
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/shop/{product}', [CatalogController::class, 'show'])->name('catalog.show');
