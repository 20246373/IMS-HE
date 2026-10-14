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

// Product Catalog Module: public storefront (guests and customers). Staff hitting / go to the dashboard.
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/shop/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// Authentication: /login asks staff or customer first, then shows that guard's form.
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.choose')->name('login');
    Route::get('/login/staff', [AuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login/staff', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Customer Account Module: storefront customers use the separate 'customer' guard (customers table).
Route::name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::get('/login/customer', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login/customer', [CustomerAuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/email/resend', [CustomerAuthController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');
    });

    Route::get('/email/verify/{customer}/{hash}', [CustomerAuthController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('logout');

        // Cart (checkout not built yet). Guests hitting these are redirected to the customer login (UC19 includes UC01).
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.add');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.remove');
    });
});

// Staff area (RBAC). Add new module routes inside this group with their own role:... middleware.
$allRoles = implode(',', User::ROLES);

Route::middleware(['auth', "role:$allRoles"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Procurement, UC12/UC13 suppliers: President, Store Supervisor. UC14 delete supplier: President only.
    Route::middleware('role:'.User::PRESIDENT.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    });
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('role:'.User::PRESIDENT)->name('suppliers.destroy');

    // Inventory, UC12-UC14 products: Inventory Clerk, Store Supervisor.
    Route::middleware('role:'.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    });

    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');

    // User account management + audit log: President only (SRS 4.3.b)
    Route::middleware('role:'.User::PRESIDENT)->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
