<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const WIDGETS = [
        User::PRESIDENT => ['accounts', 'sales', 'low-stock', 'pending-pos'],
        User::TREASURER => ['sales'],
        User::INVENTORY_CLERK => ['low-stock', 'pending-pos'],
        User::STORE_SUPERVISOR => ['sales', 'low-stock', 'pending-pos'],
        User::EMPLOYEE => [],
    ];

    public function index(): View
    {
        $widgets = self::WIDGETS[auth()->user()->role] ?? [];
        $data = [];

        foreach ($widgets as $widget) {
            $data += match ($widget) {
                'accounts' => $this->accounts(),
                'sales' => $this->sales(),
                'low-stock' => $this->lowStock(),
                'pending-pos' => $this->pendingPurchaseOrders(),
            };
        }

        return view('dashboard', $data + ['widgets' => $widgets]);
    }

    private function accounts(): array
    {
        return [
            'accounts' => User::count(),
            'locked' => User::where('status', User::STATUS_LOCKED)->count(),
        ];
    }

    private function sales(): array
    {
        $completed = Invoice::whereHas('payment')
            ->whereBetween('invoice_date', [now()->startOfMonth()->toDateString(), now()->toDateString()]);

        return [
            'monthRevenue' => (clone $completed)->sum('total_amount'),
            'monthTransactions' => (clone $completed)->count(),
        ];
    }

    private function lowStock(): array
    {
        return [
            'lowStockCount' => Product::active()->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
        ];
    }

    private function pendingPurchaseOrders(): array
    {
        return [
            'pendingPurchaseOrderCount' => PurchaseOrder::where('status', 'Pending')->count(),
            'pendingPurchaseOrders' => PurchaseOrder::with(['supplier', 'items'])
                ->where('status', 'Pending')
                ->orderByDesc('order_date')
                ->orderByDesc('po_id')
                ->limit(5)
                ->get(),
        ];
    }
}