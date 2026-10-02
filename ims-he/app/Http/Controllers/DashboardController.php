<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'todaySales' => Invoice::whereDate('invoice_date', today())->sum('total_amount'),
            'todayCount' => Invoice::whereDate('invoice_date', today())->count(),
            'lowStock'   => Product::whereColumn('stock_quantity', '<=', 'reorder_point')->orderBy('stock_quantity')->get(),
        ]);
    }
}
