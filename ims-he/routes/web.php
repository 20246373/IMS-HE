<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Public storefront (guests + customers)
Route::get('/', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Staff area (RBAC - SRS 4.3.b)
$staff = implode(',', User::STAFF_ROLES);
Route::middleware(['auth', "role:$staff"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Inventory management
    Route::middleware('role:president,inventory_clerk,store_supervisor')->prefix('manage')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
    });

    // In-store sales / POS
    Route::middleware('role:president,store_supervisor,employee')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/', [PosController::class, 'store'])->name('store');
        Route::get('/receipt/{invoice}', [PosController::class, 'receipt'])->name('receipt');
    });

    // TODO next: suppliers + purchase orders (ProcurementService), employees/attendance/payroll (PayrollService),
    // financial ledger, reports, user management (president only), customer cart + checkout.
});
