<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
| Public storefront
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');

/*
| Auth
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
| Customer cart. Guests who hit these are redirected to /login (then back).
*/
Route::middleware(['auth', 'role:' . User::CUSTOMER])->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/items', [CartController::class, 'add'])->name('add');
    Route::patch('/items/{item}', [CartController::class, 'update'])->name('update');
    Route::delete('/items/{item}', [CartController::class, 'remove'])->name('remove');
});

/*
| Staff area. Usage: ->middleware('role:president,treasurer')  (see docs/TEAM-GUIDE.md)
*/
$staff = implode(',', User::STAFF_ROLES);
Route::middleware(['auth', "role:$staff"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Account management + audit log: President only
    Route::middleware('role:president')->group(function () {
        Route::resource('accounts', AccountController::class)->except(['show'])->parameters(['accounts' => 'user']);
        Route::post('accounts/{user}/password', [AccountController::class, 'resetPassword'])->name('accounts.password');
        Route::patch('accounts/{user}/lock', [AccountController::class, 'toggleLock'])->name('accounts.lock');
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    });

    // Inventory: categories, products, stock monitoring
    Route::middleware('role:president,inventory_clerk,store_supervisor')->prefix('manage')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::resource('products', ProductController::class)->except(['show', 'destroy']);
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    });

    // Sales: POS
    Route::middleware('role:president,store_supervisor,employee')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/', [PosController::class, 'store'])->name('store');
        Route::get('/receipt/{invoice}', [PosController::class, 'receipt'])->name('receipt');
    });

    // Procurement: suppliers, purchase orders, deliveries
    Route::middleware('role:president,inventory_clerk,store_supervisor')->prefix('procurement')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show', 'create']);
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{purchaseOrder}', [DeliveryController::class, 'show'])->name('deliveries.show');
        Route::post('deliveries/{purchaseOrder}', [DeliveryController::class, 'store'])->name('deliveries.store');
    });
});
