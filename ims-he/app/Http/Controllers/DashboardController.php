<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Dashboard card => what a role needs to see it (the Administrator always sees the accounts card). */
    private const WIDGETS = [
        'sales' => 'reports.sales',
        'low-stock' => 'inventory.manage',
        'pending-pos' => 'procurement.manage',
    ];

    public function index(): View
    {
        $user = auth()->user();
        $widgets = $user->hasRole(User::ADMINISTRATOR)
            ? ['accounts']
            : array_keys(array_filter(self::WIDGETS, fn (string $permission) => $user->hasPermission($permission)));
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
            'pendingAccounts' => User::where('role', User::PENDING)->count(),
        ];
    }

    private function sales(): array
    {
        $completed = Invoice::where('status', 'Completed')
            ->whereDate('invoice_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('invoice_date', '<=', now()->toDateString());

        return [
            'monthRevenue' => (clone $completed)->sum('total_amount'),
            'monthTransactions' => (clone $completed)->count(),
        ];
    }

    private function lowStock(): array
    {
        return [
            'lowStockCount' => Product::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
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
