<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

// Order history, the confirmation shown right after checkout, and the receipt page (UC11 for online orders).
// A customer only ever sees their own orders.
class OrderController extends Controller
{
    public const STATUSES = ['Pending', 'Completed', 'Cancelled'];

    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', 'in:'.implode(',', self::STATUSES)]])['status'] ?? null;

        $orders = Invoice::with('payment')->withCount('items')
            ->where('customer_id', $request->user('customer')->customer_id)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('invoice_id')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders, 'status' => $status, 'statuses' => self::STATUSES]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeOwner($request, $invoice);

        return view('orders.show', ['invoice' => $invoice->load('items.product', 'payment', 'customer')]);
    }

    public function confirmation(Request $request, Invoice $invoice)
    {
        $this->authorizeOwner($request, $invoice);

        return view('orders.confirmation', ['invoice' => $invoice->load('items', 'payment', 'customer')]);
    }

    private function authorizeOwner(Request $request, Invoice $invoice): void
    {
        abort_unless((int) $invoice->customer_id === (int) $request->user('customer')->customer_id, 404);
    }
}
