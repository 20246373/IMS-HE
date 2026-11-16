<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SaleController;
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
    });

    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [CustomerAuthController::class, 'account'])->name('account');
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('logout');
    });
});

// Staff area (RBAC). Add new module routes inside this group with their own role:... middleware.
$allRoles = implode(',', User::ROLES);

Route::middleware(['auth', "role:$allRoles"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS / in-store sales: Employee and Store Supervisor only (SRS 4.3.d)
    Route::middleware('role:' . implode(',', [User::EMPLOYEE, User::STORE_SUPERVISOR]))->group(function () {
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{invoice}', [SaleController::class, 'show'])->name('sales.show');
    });

    // Attendance (Task B2): the Employee role clocks in and out; Store Supervisor and Treasurer view and correct.
    Route::middleware('role:' . implode(',', [User::EMPLOYEE, User::STORE_SUPERVISOR, User::TREASURER]))->group(function () {
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::middleware('role:' . User::EMPLOYEE)->group(function () {
        Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    });
    Route::middleware('role:' . implode(',', [User::STORE_SUPERVISOR, User::TREASURER]))->group(function () {
        Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    });

    // Employee management: President and Treasurer (Task B1)
    Route::middleware('role:' . implode(',', [User::PRESIDENT, User::TREASURER]))->group(function () {
        Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    });

    // Payroll (Task B3): only the Treasurer runs, finalizes or discards a payroll.
    // Keep these above the {payroll} routes so /payrolls/create is not read as an id.
    Route::middleware('role:' . User::TREASURER)->group(function () {
        Route::get('payrolls/create', [PayrollController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls', [PayrollController::class, 'store'])->name('payrolls.store');
        Route::post('payrolls/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payrolls.finalize');
        Route::delete('payrolls/{payroll}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
    });

    // Viewing payroll, payslips and the payroll report (Tasks B3 and B5): President and Treasurer.
    Route::middleware('role:' . implode(',', [User::PRESIDENT, User::TREASURER]))->group(function () {
        Route::get('payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
        Route::get('payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
        Route::get('payrolls/{payroll}/report', [PayrollReportController::class, 'show'])->name('payrolls.report');
        Route::get('payrolls/{payroll}/report/pdf', [PayrollReportController::class, 'pdf'])->name('payrolls.report.pdf');
        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');
    });

    // User account management + audit log: President only (SRS 4.3.b)
    Route::middleware('role:' . User::PRESIDENT)->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
