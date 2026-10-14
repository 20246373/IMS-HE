<?php

use App\Http\Controllers\FinancialController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Financial Recording: Treasurer (record transactions) and Admin (view everything)
Route::middleware('role:'.User::TREASURER.','.User::ADMIN)->group(function () {
    Route::get('/financial', [FinancialController::class, 'index'])->name('financial.index');
    Route::post('/financial/entries', [FinancialController::class, 'storeEntry'])->name('financial.entries.store');
    Route::delete('/financial/entries/{entry}', [FinancialController::class, 'destroyEntry'])->name('financial.entries.destroy');
    Route::put('/financial/entries/{entry}', [FinancialController::class, 'updateEntry'])->name('financial.entries.update');
});

// Financial Reporting: Admin only (statements, cash flow, export)
Route::middleware('role:'.User::ADMIN)->group(function () {
    Route::get('/financial/statements', [FinancialController::class, 'statements'])->name('financial.statements');
    Route::get('/financial/cash-flow', [FinancialController::class, 'cashFlow'])->name('financial.cash-flow');
    Route::get('/financial/export', [FinancialController::class, 'export'])->name('financial.export');
});
