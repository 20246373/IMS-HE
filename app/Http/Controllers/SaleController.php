<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InventoryTransaction;
use App\Models\LedgerEntry;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'employee', 'items.product']);

        if (auth()->user()->hasRole('Employee')) {
            $query->where('employee_id', auth()->user()->employee_id);
        }

        $invoices = $query
            ->when($request->date, fn ($q, $date) => $q->where('invoice_date', $date))
            ->when($request->customer_id, fn ($q, $id) => $q->where('customer_id', $id))
            ->orderByDesc('invoice_date')
            ->paginate(30);

        return view('sales.index', ['invoices' => $invoices]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'employee', 'items.product', 'payment']);

        return view('sales.show', ['invoice' => $invoice]);
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['customer', 'items.product', 'payment']);

        $invoice->update(['receipt_printed_at' => now()]);

        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.receipt', ['invoice' => $invoice])
                ->setPaper([0, 0, 226.77, 600])
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            return $pdf->stream('receipt_'.$invoice->invoice_id.'.pdf');
        }

        return view('sales.receipt', ['invoice' => $invoice])->with('status', 'PDF generation not available. Please install barryvdh/laravel-dompdf. Run: composer require barryvdh/laravel-dompdf');
    }

    public function onlineOrders(Request $request)
    {
        $query = Invoice::with(['customer', 'items.product'])
            ->where('channel', 'Online')
            ->where('status', 'Pending');

        $orders = $query
            ->orderByDesc('invoice_date')
            ->paginate(30);

        return view('sales.online', ['orders' => $orders]);
    }

    public function processOnlineOrder(Request $request, Invoice $invoice)
    {
        if ($invoice->channel !== 'Online' || $invoice->status !== 'Pending') {
            return back()->withErrors(['status' => 'Only pending online orders can be processed.']);
        }

        try {
            $invoice = DB::transaction(function () use ($invoice) {
                $invoice->load('items.product');

                foreach ($invoice->items as $item) {
                    $product = $item->product;
                    $qty = $item->quantity;

                    if ($product->stock_quantity < $qty) {
                        throw new \Exception("Insufficient stock for {$product->product_name} (available: {$product->stock_quantity}, needed: {$qty}).");
                    }

                    $product->decrement('stock_quantity', $qty);

                    InventoryTransaction::create([
                        'product_id' => $product->product_id,
                        'transaction_type' => 'Sale',
                        'quantity' => $qty,
                        'transaction_date' => now()->toDateString(),
                        'reference_type' => 'Invoice',
                        'reference_id' => $invoice->invoice_id,
                    ]);
                }

                $invoice->update([
                    'status' => 'Completed',
                    'employee_id' => auth()->user()->employee_id ?? null,
                ]);

                Payment::create([
                    'invoice_id' => $invoice->invoice_id,
                    'payment_date' => now()->toDateString(),
                    'amount_paid' => $invoice->total_amount,
                    'change_amount' => 0,
                    'payment_method' => 'Cash',
                ]);

                LedgerEntry::create([
                    'entry_date' => now()->toDateString(),
                    'entry_type' => 'Income',
                    'amount' => $invoice->total_amount,
                    'description' => "Online Order - Invoice #{$invoice->invoice_id} (Customer: {$invoice->customer->username})",
                    'reference_type' => 'Invoice',
                    'reference_id' => $invoice->invoice_id,
                    'recorded_by' => auth()->id(),
                ]);

                return $invoice->load('items.product', 'payment', 'customer');
            });

            return redirect()->route('sales.online')->with('status', 'Order processed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
