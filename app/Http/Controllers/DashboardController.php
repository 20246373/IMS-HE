<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $data = [];

        if ($user->hasRole(User::ADMIN)) {
            $data['accounts'] = User::count();
            $data['locked'] = User::whereNotNull('locked_at')->count();
            $data['totalIncome'] = LedgerEntry::where('entry_type', 'income')->sum('amount');
            $data['totalExpense'] = LedgerEntry::where('entry_type', 'expense')->sum('amount');
        }

        if ($user->hasRole(User::TREASURER)) {
            $data['totalIncome'] = LedgerEntry::where('entry_type', 'income')->sum('amount');
            $data['totalExpense'] = LedgerEntry::where('entry_type', 'expense')->sum('amount');
        }

        if ($user->hasRole(User::ADMIN, User::INVENTORY_CLERK, User::STORE_SUPERVISOR)) {
            $data['lowStockCount'] = Product::whereColumn('stock_quantity', '<=', 'reorder_level')->count();
            $data['pendingPurchaseOrderCount'] = PurchaseOrder::where('status', 'Pending')->count();
            $data['pendingPurchaseOrders'] = PurchaseOrder::with(['supplier', 'items'])
                ->where('status', 'Pending')
                ->orderByDesc('order_date')
                ->orderByDesc('po_id')
                ->limit(5)
                ->get();
        }

        if ($user->hasRole(User::ADMIN, User::STORE_SUPERVISOR, User::EMPLOYEE)) {
            $data['todaySales'] = Invoice::where('invoice_date', today())->count();
            $data['todaySalesAmount'] = Invoice::where('invoice_date', today())->sum('total_amount');
        }

        return view('dashboard', $data);
    }
}
