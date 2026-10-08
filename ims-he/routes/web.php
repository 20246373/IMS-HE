<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Entry point: staff go to the dashboard, everyone else to login.
// (Person 5 will replace this with the public storefront.)
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Staff area (RBAC). Add new module routes inside this group with their own role:... middleware.
$allRoles = implode(',', User::ROLES);

Route::middleware(['auth', "role:$allRoles"])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User account management + audit log: President only (SRS 4.3.b)
    Route::middleware('role:' . User::PRESIDENT)->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
