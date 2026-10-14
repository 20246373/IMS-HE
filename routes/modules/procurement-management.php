<?php

use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Procurement Management: Admin, Inventory Clerk and Store Supervisor
Route::middleware('role:'.User::ADMIN.','.User::INVENTORY_CLERK.','.User::STORE_SUPERVISOR)->group(function () {
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
});

// Inventory Clerk only: manage suppliers and delete POs
Route::middleware('role:'.User::INVENTORY_CLERK)->group(function () {
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
});
