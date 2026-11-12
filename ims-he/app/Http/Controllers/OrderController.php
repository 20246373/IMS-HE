<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

// Order history and the receipt page (UC11 for online orders). A customer only ever sees their own orders.
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Invoice::with('payment')->withCount('items')
            ->where('customer_id', $request->user('customer')->customer_id)
            ->orderByDesc('invoice_id')
            ->paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->customer_id === (int) $request->user('customer')->customer_id, 404);

        return view('orders.show', ['invoice' => $invoice->load('items.product', 'payment', 'customer')]);
    }
}
