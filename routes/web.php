<?php

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Public routes
require __DIR__.'/modules/catalog.php';

// Customer routes (customer guard)
require __DIR__.'/modules/customer.php';

// Staff authentication routes
require __DIR__.'/modules/staff-auth.php';

// Staff dashboard (all authenticated staff)
Route::middleware(['auth', 'role:'.implode(',', User::ROLES)])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Module routes with role-based access control
Route::middleware(['auth'])->group(function () {
    // User Account Management: Admin only
    require __DIR__.'/modules/user-account-management.php';

    // Financial Recording and Reporting: Admin and Treasurer
    require __DIR__.'/modules/financial-recording.php';

    // Inventory Management: Admin, Inventory Clerk, and Store Supervisor
    require __DIR__.'/modules/inventory-management.php';

    // Procurement Management: Admin, Inventory Clerk, and Store Supervisor
    require __DIR__.'/modules/procurement-management.php';

    // Payroll Processing: Admin and Treasurer
    require __DIR__.'/modules/payroll-processing.php';

    // Sales Management: Admin, Store Supervisor, and Employee
    require __DIR__.'/modules/sales-management.php';

    // Report Generation: Admin, Treasurer, and Store Supervisor
    require __DIR__.'/modules/report-generation.php';
});
