<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/shop/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// One login page for customers and staff. Sign up is email + password only.
Route::middleware('guest:web,customer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{guard}/{id}/{hash}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password/{guard}/{id}/{hash}', [AuthController::class, 'resetPassword']);

    // Employees sign up the same way, then wait for the Administrator to give them a role.
    Route::get('/register/employee', [AuthController::class, 'showRegister'])->name('staff.register');
    Route::post('/register/employee', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Old login URLs still work (bookmarks, old links).
    Route::redirect('/login/staff', '/login')->name('staff.login');
    Route::redirect('/login/customer', '/login')->name('customer.login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Waiting page for employees who have no role yet (not behind the role middleware).
Route::get('/pending', [AuthController::class, 'pending'])
    ->middleware('auth')
    ->name('staff.pending');

Route::name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/email/resend', [CustomerAuthController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('verification.resend');
    });

    Route::get('/email/verify/{customer}/{hash}', [CustomerAuthController::class, 'verify'])
        ->name('verification.verify');   // signature is checked inside verify()

    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
        Route::patch('/account', [CustomerAuthController::class, 'updateProfile'])->name('account.update');
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('logout');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.add');
        Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.remove');

        // UC20 Checkout, then the receipt and order history
        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{invoice}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{invoice}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
    });
});

$allRoles = implode(',', User::ROLES);

Route::middleware(['auth', "role:$allRoles"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS / in-store sales: Employee and Store Supervisor only
    Route::middleware('role:'.implode(',', [User::EMPLOYEE, User::STORE_SUPERVISOR]))->group(function () {
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{invoice}', [SaleController::class, 'show'])->name('sales.show');
    });

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    });

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('role:'.User::PRESIDENT)
        ->name('suppliers.destroy');

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::get('products/{product}/stock-history', [ProductController::class, 'stockHistory'])
            ->name('products.stock-history');
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    });

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel');
        Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    });

    // Online order management (task J4): list online orders, complete or cancel Pending ones.
    Route::middleware('role:'.User::PRESIDENT.','.User::STORE_SUPERVISOR.','.User::EMPLOYEE)->group(function () {
        Route::get('online-orders', [OrderManagementController::class, 'index'])->name('online-orders.index');
        Route::get('online-orders/{invoice}', [OrderManagementController::class, 'show'])->name('online-orders.show');
        Route::patch('online-orders/{invoice}/complete', [OrderManagementController::class, 'complete'])->name('online-orders.complete');
        Route::patch('online-orders/{invoice}/cancel', [OrderManagementController::class, 'cancel'])->name('online-orders.cancel');
    });

    Route::middleware('role:'.User::ADMINISTRATOR)->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::post('users/{user}/role', [UserController::class, 'assignRole'])->name('users.role');
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

    Route::middleware('role:'.User::PRESIDENT.','.User::INVENTORY_CLERK)->group(function () {
        Route::get('reports/procurement', [ReportController::class, 'procurement'])->name('reports.procurement');
        Route::get('reports/procurement/download', [ReportController::class, 'downloadProcurement'])
            ->name('reports.procurement.download');
    });

    Route::middleware('role:'.User::PRESIDENT.','.User::TREASURER.','.User::STORE_SUPERVISOR)->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/download', [ReportController::class, 'downloadSales'])
            ->name('reports.sales.download');
    });

    Route::middleware('role:'.User::ADMINISTRATOR.','.User::PRESIDENT.','.User::TREASURER.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
        Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('exports/{dataset}', [ExportController::class, 'download'])->name('exports.download');
        Route::post('exports-all', [ExportController::class, 'all'])->name('exports.all');
    });

    Route::middleware('role:'.User::ADMINISTRATOR)->group(function () {
        Route::get('exports-backup', [ExportController::class, 'backup'])->name('exports.backup');
    });
});
