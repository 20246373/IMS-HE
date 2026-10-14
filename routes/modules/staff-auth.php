<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Staff authentication and session management.
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.choose')->name('login');
    Route::get('/login/staff', [AuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login/staff', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
