<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// User Account Management and Audit Log: Admin only.
Route::middleware('role:'.User::ADMIN)->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
    Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
    Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
});
