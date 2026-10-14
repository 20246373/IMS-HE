<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ReportController;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/shop/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.choose')->name('login');
    Route::get('/login/staff', [AuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login/staff', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::get('/login/customer', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login/customer', [CustomerAuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/email/resend', [CustomerAuthController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('verification.resend');
    });

    Route::get('/email/verify/{customer}/{hash}', [CustomerAuthController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('logout');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.add');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.remove');
    });
});

$allRoles = implode(',', User::ROLES);

Route::middleware(['auth', "role:$allRoles"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    });

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('role:'.User::PRESIDENT)
        ->name('suppliers.destroy');

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    });

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    });

    Route::middleware('role:'.User::PRESIDENT)->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware('role:'.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR.','.User::PRESIDENT)->group(function () {
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/inventory/download', [ReportController::class, 'downloadInventory'])
            ->name('reports.inventory.download');
    });

    Route::middleware('role:'.User::PRESIDENT.','.User::TREASURER.','.User::STORE_SUPERVISOR)->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/download', [ReportController::class, 'downloadSales'])
            ->name('reports.sales.download');
    });
});