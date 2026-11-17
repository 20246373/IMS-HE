<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function create(Request $request): View
    {
        $data = $request->validate([
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,po_id'],
        ]);

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('status', 'Pending')
            ->orderByDesc('order_date')
            ->orderByDesc('po_id')
            ->get();
        $selectedOrder = null;

        if (isset($data['purchase_order_id'])) {
            $selectedOrder = PurchaseOrder::with(['supplier', 'items.product'])
                ->whereKey($data['purchase_order_id'])
                ->where('status', 'Pending')
                ->first();

            if ($selectedOrder === null) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Only Pending purchase orders can receive deliveries.',
                ]);
            }
        }

        return view('deliveries.create', compact('purchaseOrders', 'selectedOrder'));
    }

    public function store(Request $request, ProcurementService $procurement): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,po_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.po_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_order_items,po_item_id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        $purchaseOrder = $procurement->receive(
            PurchaseOrder::findOrFail($data['purchase_order_id']),
            $data['items'],
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('status', 'Delivery quantities recorded.');
    }
}
