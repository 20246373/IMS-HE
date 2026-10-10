<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index', [
            'products'  => Product::active()->where('stock_quantity', '>', 0)->orderBy('name')->get(),
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request, SaleService $sales)
    {
        $data = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'customer_id'        => 'nullable|exists:customers,id',   // empty = walk-in
            'amount_paid'        => 'required|numeric|min:0',
            'payment_method'     => 'required|in:Cash,GCash',
        ]);

        $invoice = $sales->checkout(
            $data['items'],
            (float) $data['amount_paid'],
            $data['payment_method'],
            $data['customer_id'] ?? null,
            $request->user()->id,
            'In-store',
        );

        return redirect()->route('pos.receipt', $invoice);
    }

    public function receipt(Invoice $invoice)
    {
        return view('pos.receipt', ['invoice' => $invoice->load('items.product', 'payment', 'user', 'customer')]);
    }
}
