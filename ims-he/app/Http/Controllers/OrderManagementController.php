<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\OrderManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Staff list of online orders (Employee, Store Supervisor, President). Business rules live in OrderManagementService.
class OrderManagementController extends Controller
{
    public const STATUSES = ['Pending', 'Completed', 'Cancelled'];

    public function __construct(private OrderManagementService $orders) {}

    public function index(Request $request)
    {
        // All orders by default, like every other filter; staff can narrow it to Pending.
        $status = $request->validate(['status' => ['nullable', Rule::in([...self::STATUSES, 'All'])]])['status'] ?? 'All';

        $orders = Invoice::with('customer', 'payment')->withCount('items')
            ->where('channel', 'Online')
            ->when($status !== 'All', fn ($q) => $q->where('status', $status))
            ->orderByDesc('invoice_id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.manage.index', ['orders' => $orders, 'status' => $status, 'statuses' => self::STATUSES]);
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->channel === 'Online', 404);

        return view('orders.manage.show', ['invoice' => $invoice->load('items.product', 'customer', 'payment')]);
    }

    public function complete(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->channel === 'Online', 404);
        $this->orders->complete($invoice, $request->user());

        return redirect()->route('online-orders.show', $invoice)->with('status', "Order #{$invoice->invoice_id} marked as Completed.");
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->channel === 'Online', 404);
        $this->orders->cancel($invoice, $request->user());

        return redirect()->route('online-orders.show', $invoice)->with('status', "Order #{$invoice->invoice_id} cancelled. Its stock was returned to inventory.");
    }
}
