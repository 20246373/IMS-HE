<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index', ['products' => Product::where('stock_quantity', '>', 0)->orderBy('name')->get()]);
    }

    public function store(Request $request, SaleService $sales)
    {
        $data = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'amount_paid'        => 'required|numeric|min:0',
            'payment_method'     => 'required|in:Cash,GCash',
        ]);

        $invoice = $sales->checkout($data['items'], (float) $data['amount_paid'], $data['payment_method'], null, $request->user()->id);

        return redirect()->route('pos.receipt', $invoice);
    }

    public function receipt(Invoice $invoice)
    {
        return view('pos.receipt', ['invoice' => $invoice->load('items.product', 'payment', 'user')]);
    }
}
