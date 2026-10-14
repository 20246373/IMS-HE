<?php

use App\Http\Controllers\ReportController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Report Generation
Route::middleware('role:'.User::ADMIN.','.User::TREASURER.','.User::STORE_SUPERVISOR)->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

// Admin: all reports with CSV export
Route::middleware('role:'.User::ADMIN)->group(function () {
    Route::get('/reports/company', [ReportController::class, 'company'])->name('reports.company');
    Route::get('/reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');
});

// Treasurer: financial and payroll reports
Route::middleware('role:'.User::TREASURER)->group(function () {
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('reports.payroll');
});

// Store Supervisor: sales and inventory reports
Route::middleware('role:'.User::STORE_SUPERVISOR)->group(function () {
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
});
