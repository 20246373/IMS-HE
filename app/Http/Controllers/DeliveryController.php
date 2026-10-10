<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /** Pick a pending PO. */
    public function index()
    {
        return view('deliveries.index', ['orders' => PurchaseOrder::with('supplier')->where('status', 'Pending')->orderBy('id')->get()]);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Pending') {
            return redirect()->route('deliveries.index')->withErrors(['status' => "PO #{$purchaseOrder->id} is already {$purchaseOrder->status}."]);
        }

        return view('deliveries.show', ['po' => $purchaseOrder->load('supplier', 'items.product')]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder, ProcurementService $procurement)
    {
        $d = $request->validate([
            'received'   => 'required|array',
            'received.*' => 'nullable|integer|min:0',
        ]);

        $po = $procurement->receive($purchaseOrder, $d['received']);

        return redirect()->route('purchase-orders.show', $po)
            ->with('status', $po->status === 'Delivered' ? "PO #{$po->id} fully received." : "Delivery recorded. PO #{$po->id} still has outstanding items.");
    }
}
