<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(
        private InventoryService $inventory,
        private FinancialService $financial,
    ) {}

    /**
     * Creates a Pending, auto-generated PO when a deduction leaves a product at or below
     * its reorder level. Returns null when no PO is needed or the product has no supplier.
     */
    public function createAutoPurchaseOrder(Product|int $product): ?PurchaseOrder
    {
        $product = $product instanceof Product ? $product : Product::find($product);

        if ($product === null || (int) $product->stock_quantity > (int) $product->reorder_level) {
            return null;
        }

        if ($product->supplier_id === null) {
            $warning = "{$product->product_name} is low on stock with no default supplier. Assign a supplier so it can be reordered.";

            // Do not leave a warning behind if the surrounding sale/checkout rolls back.
            DB::afterCommit(fn () => session()->flash('warning', $warning));

            return null;
        }

        $alreadyOnOrder = PurchaseOrderItem::query()
            ->where('product_id', $product->product_id)
            ->whereHas('purchaseOrder', fn ($query) => $query->where('status', 'Pending'))
            ->exists();

        if ($alreadyOnOrder) {
            return null;
        }

        $multiplier = max(1, (int) config('inventory.auto_po_multiplier', 2));
        $quantity = max(1, (int) $product->reorder_level * $multiplier);

        $lastItem = PurchaseOrderItem::query()
            ->where('product_id', $product->product_id)
            ->orderByDesc('po_item_id')
            ->first();

        $unitCost = $lastItem !== null ? (float) $lastItem->unit_cost : (float) $product->unit_price;

        return DB::transaction(function () use ($product, $quantity, $unitCost): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $product->supplier_id,
                'order_date' => today(),
                'status' => 'Pending',
                'is_auto' => true,
            ]);

            PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->po_id,
                'product_id' => $product->product_id,
                'quantity' => $quantity,
                'quantity_received' => 0,
                'unit_cost' => $unitCost,
                'subtotal' => round($quantity * $unitCost, 2),
            ]);

            AuditLog::create([
                'user_id' => Auth::guard('web')->id(),
                'action' => 'Created',
                'table_name' => 'purchase_orders',
                'record_id' => $purchaseOrder->po_id,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Records a full or partial delivery against a Pending PO. Stock is added through
     * InventoryService, and the PO becomes Delivered only when every line is complete.
     * Pass a po_item_id => quantity map or the submitted items array; null receives every remaining unit.
     *
     * @param  array<int|string, int|array{po_item_id:int, quantity_received:int}>|null  $receivedQuantities
     */
    public function receive(PurchaseOrder $po, ?array $receivedQuantities = null): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedQuantities) {
            $po = PurchaseOrder::whereKey($po->po_id)->lockForUpdate()->firstOrFail();

            if ($po->status !== 'Pending') {
                throw ValidationException::withMessages([
                    'purchase_order_id' => "PO #{$po->po_id} is already {$po->status}.",
                ]);
            }

            $items = $po->items()->orderBy('po_item_id')->lockForUpdate()->get();
            $quantities = $this->normalizeReceivedQuantities(
                $receivedQuantities ?? $items->mapWithKeys(fn (PurchaseOrderItem $item) => [
                    (int) $item->po_item_id => (int) $item->quantity - (int) $item->quantity_received,
                ])->all(),
            );

            $itemsById = $items->keyBy('po_item_id');
            $validationErrors = [];

            foreach ($quantities as $poItemId => $entry) {
                $purchaseOrderItem = $itemsById->get((int) $poItemId);

                if ($purchaseOrderItem === null) {
                    $validationErrors['items'] = 'One or more delivery lines do not belong to this purchase order.';
                    break;
                }

                $newReceivedQuantity = $entry['quantity'];
                $remainingQuantity = (int) $purchaseOrderItem->quantity - (int) $purchaseOrderItem->quantity_received;

                if ($newReceivedQuantity < 0 || $newReceivedQuantity > $remainingQuantity) {
                    $validationErrors[$entry['error_key']] = sprintf(
                        'Cannot receive %d; only %d remain from the ordered quantity of %d.',
                        $newReceivedQuantity,
                        max(0, $remainingQuantity),
                        $purchaseOrderItem->quantity,
                    );
                }
            }

            if ($validationErrors !== []) {
                throw ValidationException::withMessages($validationErrors);
            }

            $deliveryValue = 0.0;

            foreach ($quantities as $poItemId => $entry) {
                $purchaseOrderItem = $itemsById->get((int) $poItemId);
                $newReceivedQuantity = $entry['quantity'];

                if ($newReceivedQuantity > 0) {
                    $this->inventory->add(
                        (int) $purchaseOrderItem->product_id,
                        $newReceivedQuantity,
                        'PO',
                        (int) $po->po_id,
                    );

                    $deliveryValue += $newReceivedQuantity * (float) $purchaseOrderItem->unit_cost;
                }

                $purchaseOrderItem->quantity_received += $newReceivedQuantity;
                $purchaseOrderItem->save();
            }

            if ($items->every(fn (PurchaseOrderItem $item) => (int) $item->quantity_received >= (int) $item->quantity)) {
                $po->update(['status' => 'Delivered']);
            }

            // Financial Recording (SDD 7.2.18): goods received are an Expense,
            // written inside the same transaction as the stock update.
            if ($deliveryValue > 0) {
                $this->financial->recordExpense(
                    'PO',
                    (int) $po->po_id,
                    round($deliveryValue, 2),
                    "Delivery received - PO #{$po->po_id}",
                    Auth::guard('web')->id(),
                );
            }

            $po->setRelation('items', $items);

            return $po;
        });
    }

    /**
     * @param  array<int|string, int|array{po_item_id:int, quantity_received:int}>  $receivedQuantities
     * @return array<int, array{quantity:int, error_key:string}>
     */
    private function normalizeReceivedQuantities(array $receivedQuantities): array
    {
        if (isset($receivedQuantities['items']) && is_array($receivedQuantities['items'])) {
            $receivedQuantities = $receivedQuantities['items'];
        }

        $normalized = [];

        foreach ($receivedQuantities as $key => $value) {
            if (is_array($value)) {
                $poItemId = (int) ($value['po_item_id'] ?? 0);
                $quantity = (int) ($value['quantity_received'] ?? 0);
                $errorKey = is_int($key)
                    ? "items.{$key}.quantity_received"
                    : "items.{$poItemId}.quantity_received";
            } else {
                $poItemId = (int) $key;
                $quantity = (int) $value;
                $errorKey = "items.{$poItemId}.quantity_received";
            }

            if ($poItemId > 0) {
                $normalized[$poItemId] = ['quantity' => $quantity, 'error_key' => $errorKey];
            }
        }

        return $normalized;
    }
}
