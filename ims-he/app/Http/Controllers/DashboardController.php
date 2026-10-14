<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $isPresident = auth()->user()->hasRole(User::PRESIDENT);

        return view('dashboard', [
            'accounts' => $isPresident ? User::count() : null,
            'locked' => $isPresident ? User::where('status', User::STATUS_LOCKED)->count() : null,
            'lowStockCount' => Product::active()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
            'pendingPurchaseOrderCount' => PurchaseOrder::where('status', 'Pending')->count(),
            'pendingPurchaseOrders' => PurchaseOrder::with(['supplier', 'items'])
                ->where('status', 'Pending')
                ->orderByDesc('order_date')
                ->orderByDesc('po_id')
                ->limit(5)
                ->get(),
        ]);
    }
}
