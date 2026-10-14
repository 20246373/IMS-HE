<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Payroll Processing: Admin and Treasurer
Route::middleware('role:'.User::ADMIN.','.User::TREASURER)->group(function () {
    Route::resource('employees', EmployeeController::class)->except(['show']);
    Route::get('payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::get('payrolls/create', [PayrollController::class, 'create'])->name('payrolls.create');
    Route::post('payrolls', [PayrollController::class, 'store'])->name('payrolls.store');
    Route::get('payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::post('payrolls/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payrolls.finalize');
    Route::get('payslips/{payslip}', [PayrollController::class, 'payslip'])->name('payslips.show');
});

// Attendance Management: Treasurer (via payroll) and Store Supervisor
Route::middleware('role:'.User::TREASURER.','.User::STORE_SUPERVISOR)->group(function () {
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
});
