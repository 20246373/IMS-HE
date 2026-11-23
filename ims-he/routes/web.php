<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
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

    // Sign up starts by choosing Customer or Employee.
    Route::get('/register', [AuthController::class, 'chooseRegister'])->name('register');

    // Employees sign up the same way, then wait for the Administrator to give them a role.
    Route::get('/register/employee', [AuthController::class, 'showRegister'])->name('staff.register');
    Route::post('/register/employee', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Old login URLs still work (bookmarks, old links).
    Route::redirect('/login/staff', '/login?as=employee')->name('staff.login');
    Route::redirect('/login/customer', '/login?as=customer')->name('customer.login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Accounts the Administrator created must replace their default password before using anything else.
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.update');
});

// Waiting page for employees who have no role yet (not behind the role middleware).
Route::get('/pending', [AuthController::class, 'pending'])
    ->middleware('auth')
    ->name('staff.pending');

Route::name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register/customer', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register/customer', [CustomerAuthController::class, 'register']);
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

// Staff area. Each group needs a permission (App\Support\Permissions); the Administrator decides on the Roles page
// which roles have which permissions. Accounts, roles and the audit log stay Administrator-only.
Route::middleware(['auth', 'permission'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update']);   // add and edit happen in pop-ups
    });

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete')
        ->name('suppliers.destroy');

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);   // edit happens in a pop-up
        Route::get('products/{product}/stock-history', [ProductController::class, 'stockHistory'])
            ->name('products.stock-history');
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    });

    Route::middleware('permission:procurement.manage')->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel');
        Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
        Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    });

    // Online order management (task J4): list online orders, complete or cancel Pending ones.
    Route::middleware('permission:online_orders.manage')->group(function () {
        Route::get('online-orders', [OrderManagementController::class, 'index'])->name('online-orders.index');
        Route::get('online-orders/{invoice}', [OrderManagementController::class, 'show'])->name('online-orders.show');
        Route::patch('online-orders/{invoice}/complete', [OrderManagementController::class, 'complete'])->name('online-orders.complete');
        Route::patch('online-orders/{invoice}/cancel', [OrderManagementController::class, 'cancel'])->name('online-orders.cancel');
    });

    Route::middleware('permission:sales.pos')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales/{invoice}', [SaleController::class, 'show'])->name('sales.show');
    });
    Route::middleware('permission:attendance.own')->group(function () {
        Route::get('attendance/my', [AttendanceController::class, 'my'])->name('attendance.my');
        Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    });
    Route::middleware('permission:attendance.manage')->group(function () {
        Route::get('attendance/day', [AttendanceController::class, 'day'])->name('attendance.day');
        Route::post('attendance/day', [AttendanceController::class, 'saveDay'])->name('attendance.day.save');
        Route::get('attendance/period', [AttendanceController::class, 'period'])->name('attendance.period');
    });

    Route::middleware('role:'.User::ADMINISTRATOR)->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('users/{user}/role', [UserController::class, 'assignRole'])->name('users.role');
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        // The Administrator's only export: the audit log (CSV, same format as the Export Center).
        Route::get('audit-logs-export', [AuditLogController::class, 'export'])->name('audit.export');
        Route::resource('roles', RoleController::class)->except(['show']);

        // Customers' storefront logins (account data only; order history is on the business Customers page).
        Route::resource('customer-accounts', CustomerAccountController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->parameters(['customer-accounts' => 'customer']);
        Route::post('customer-accounts/{customer}/verify', [CustomerAccountController::class, 'verify'])->name('customer-accounts.verify');
        Route::post('customer-accounts/{customer}/password', [CustomerAccountController::class, 'resetPassword'])->name('customer-accounts.password');
    });

    Route::middleware('permission:reports.inventory')->group(function () {
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/inventory/download', [ReportController::class, 'downloadInventory'])
            ->name('reports.inventory.download');
    });

    Route::middleware('permission:reports.procurement')->group(function () {
        Route::get('reports/procurement', [ReportController::class, 'procurement'])->name('reports.procurement');
        Route::get('reports/procurement/download', [ReportController::class, 'downloadProcurement'])
            ->name('reports.procurement.download');
    });

    Route::middleware('permission:reports.sales')->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/download', [ReportController::class, 'downloadSales'])
            ->name('reports.sales.download');
    });

    Route::middleware('permission:ledger.view')->group(function () {
        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
    });
    Route::middleware('permission:employees.manage')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('employees/{employee}/resign', [EmployeeController::class, 'resign'])->name('employees.resign');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::get('customers', [CustomerManagementController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}/edit', [CustomerManagementController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerManagementController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerManagementController::class, 'destroy'])->name('customers.destroy');
        Route::get('customers/{customer}', [CustomerManagementController::class, 'show'])->name('customers.show');
    });
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    });
    Route::middleware('permission:payroll.run')->group(function () {
        Route::get('payrolls/create', [PayrollController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls', [PayrollController::class, 'store'])->name('payrolls.store');
    });
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
        Route::get('payrolls/{payroll}/payslips/{payslip}', [PayrollController::class, 'payslip'])->name('payrolls.payslip');
        Route::get('payrolls/{payroll}/download', [PayrollController::class, 'download'])->name('payrolls.download');
    });
    Route::middleware('permission:payroll.run')->group(function () {
        Route::patch('payrolls/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payrolls.finalize');
        Route::delete('payrolls/{payroll}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
    });
    Route::middleware('permission:ledger.record')->group(function () {
        Route::get('ledger/create', [LedgerController::class, 'create'])->name('ledger.create');
        Route::post('ledger', [LedgerController::class, 'store'])->name('ledger.store');
        Route::post('ledger/{ledger_entry}/reverse', [LedgerController::class, 'reverse'])->name('ledger.reverse');
    });
    Route::middleware('permission:reports.financial')->group(function () {
        Route::get('reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
        Route::get('reports/financial/download', [FinancialReportController::class, 'download'])
            ->name('reports.financial.download');
    });

    Route::middleware('permission:exports.inventory,exports.suppliers,exports.sales,exports.payroll,exports.customers')->group(function () {
        Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('exports/{dataset}', [ExportController::class, 'download'])->name('exports.download');
        Route::post('exports-all', [ExportController::class, 'all'])->name('exports.all');
    });

    Route::middleware('role:'.User::ADMINISTRATOR)->group(function () {
        Route::get('exports-backup', [ExportController::class, 'backup'])->name('exports.backup');
    });
});
