<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index()
    {
        return view('sales.index', [
            'invoices' => Invoice::with('payment')->latest('invoice_id')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('sales.create', [
            'products' => Product::where('is_active', true)->orderBy('product_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,product_id',
            'items.*.quantity'       => 'required|integer|min:1',
            'amount_paid'            => 'required|numeric|min:0',
            'payment_method'         => ['required', Rule::in(['Cash', 'GCash'])],
        ]);

        $invoice = $this->sales->checkout(
            $data['items'],
            (float) $data['amount_paid'],
            $data['payment_method'],
            null,
            $request->user()->user_id,
        );

        return redirect()->route('sales.show', $invoice->invoice_id)
            ->with('status', "Sale completed - Invoice #{$invoice->invoice_id}.");
    }

    public function show(Invoice $invoice)
    {
        return view('sales.show', [
            'invoice' => $invoice->load('items.product', 'payment', 'customer', 'employee'),
        ]);
    }
}
