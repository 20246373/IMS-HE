<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::active()->with('category')
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_point'))
            ->orderByRaw('(stock_quantity <= reorder_point) desc')->orderBy('name')
            ->get();

        return view('stock.index', [
            'products'     => $products,
            'transactions' => InventoryTransaction::with('product')->latest('id')->limit(15)->get(),
        ]);
    }
}
