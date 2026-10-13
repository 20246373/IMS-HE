<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,po_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.po_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_order_items,po_item_id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        $purchaseOrder = DB::transaction(function () use ($data, $inventory): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::whereKey($data['purchase_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchaseOrder->status !== 'Pending') {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Only Pending purchase orders can receive deliveries.',
                ]);
            }

            $purchaseOrderItems = $purchaseOrder->items()
                ->orderBy('po_item_id')
                ->lockForUpdate()
                ->get();
            $submittedItems = collect($data['items']);
            $expectedItemIds = $purchaseOrderItems->pluck('po_item_id')->map(fn ($id) => (int) $id);
            $submittedItemIds = $submittedItems->pluck('po_item_id')->map(fn ($id) => (int) $id);

            if (
                $submittedItemIds->count() !== $expectedItemIds->count()
                || $submittedItemIds->diff($expectedItemIds)->isNotEmpty()
                || $expectedItemIds->diff($submittedItemIds)->isNotEmpty()
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Submit a delivery quantity for every line in this purchase order.',
                ]);
            }

            $itemsById = $purchaseOrderItems->keyBy('po_item_id');
            $validationErrors = [];

            foreach ($submittedItems as $index => $submittedItem) {
                $purchaseOrderItem = $itemsById->get((int) $submittedItem['po_item_id']);
                $newReceivedQuantity = (int) $submittedItem['quantity_received'];
                $totalReceivedQuantity = (int) $purchaseOrderItem->quantity_received + $newReceivedQuantity;

                if ($totalReceivedQuantity > (int) $purchaseOrderItem->quantity) {
                    $validationErrors["items.{$index}.quantity_received"] = sprintf(
                        'Cannot receive %d more; only %d remain from the ordered quantity of %d.',
                        $newReceivedQuantity,
                        max(0, (int) $purchaseOrderItem->quantity - (int) $purchaseOrderItem->quantity_received),
                        $purchaseOrderItem->quantity,
                    );
                }
            }

            if ($validationErrors !== []) {
                throw ValidationException::withMessages($validationErrors);
            }

            foreach ($submittedItems as $submittedItem) {
                $purchaseOrderItem = $itemsById->get((int) $submittedItem['po_item_id']);
                $newReceivedQuantity = (int) $submittedItem['quantity_received'];

                if ($newReceivedQuantity > 0) {
                    $inventory->add(
                        (int) $purchaseOrderItem->product_id,
                        $newReceivedQuantity,
                        'PO',
                        $purchaseOrder->po_id,
                    );
                }

                $purchaseOrderItem->quantity_received += $newReceivedQuantity;
                $purchaseOrderItem->save();
            }

            if ($purchaseOrderItems->every(fn ($item) => (int) $item->quantity_received >= (int) $item->quantity)) {
                $purchaseOrder->update(['status' => 'Delivered']);
            }

            return $purchaseOrder;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('status', 'Delivery quantities recorded.');
    }
}
