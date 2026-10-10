<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\PurchaseOrder;
use App\Support\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @param array<int, array{product_id:int, quantity:int, unit_cost:float}> $lines
     * Creates a Pending PO with computed subtotals.
     */
    public function create(int $supplierId, array $lines): PurchaseOrder
    {
        if (empty($lines)) {
            throw ValidationException::withMessages(['lines' => 'Add at least one product line.']);
        }

        return DB::transaction(function () use ($supplierId, $lines) {
            $po = PurchaseOrder::create([
                'supplier_id' => $supplierId,
                'order_date'  => today(),
                'status'      => 'Pending',
            ]);

            foreach ($lines as $l) {
                $qty = (int) $l['quantity'];
                $cost = (float) $l['unit_cost'];
                $po->items()->create([
                    'product_id'        => $l['product_id'],
                    'quantity'          => $qty,
                    'quantity_received' => 0,
                    'unit_cost'         => $cost,
                    'subtotal'          => round($qty * $cost, 2),
                ]);
            }

            AuditLog::record('po.created', $po, "PO #{$po->id} created with {$po->items->count()} line(s)");

            return $po;
        });
    }

    /**
     * Confirms a (possibly partial) delivery.
     * @param array<int|string, int|string> $received  purchase_order_items.id => quantity received now
     * Blocks anything over the ordered quantity, adds stock via InventoryService::add(),
     * and marks the PO Delivered once every line is fully received. One DB transaction.
     */
    public function receive(PurchaseOrder $po, array $received): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $received) {
            $po = PurchaseOrder::lockForUpdate()->with('items.product')->findOrFail($po->id);

            if ($po->status !== 'Pending') {
                throw ValidationException::withMessages(['status' => "PO #{$po->id} is already {$po->status}."]);
            }

            $anyReceived = false;
            $cost = 0.0;

            $validItemIds = $po->items->pluck('id')->toArray();
            foreach (array_keys($received) as $itemId) {
                if (!in_array((int)$itemId, $validItemIds, true)) {
                    throw ValidationException::withMessages(['received' => "Invalid line item ID {$itemId}."]);
                }
            }

            foreach ($po->items as $item) {
                $qty = (int) ($received[$item->id] ?? 0);
                if ($qty < 0) {
                    throw ValidationException::withMessages(['received' => "Invalid quantity for {$item->product->name}."]);
                }
                if ($qty === 0) {
                    continue;
                }

                $outstanding = $item->quantity - $item->quantity_received;
                if ($qty > $outstanding) {
                    throw ValidationException::withMessages([
                        'received' => "{$item->product->name}: cannot receive {$qty}; only {$outstanding} still outstanding.",
                    ]);
                }

                $this->inventory->add($item->product_id, $qty, 'PO', $po->id);
                $item->increment('quantity_received', $qty);
                $cost += $qty * (float) $item->unit_cost;
                $anyReceived = true;
            }

            if (! $anyReceived) {
                throw ValidationException::withMessages(['received' => 'Enter a received quantity for at least one line.']);
            }

            $po->load('items');
            if ($po->items->every(fn ($i) => $i->quantity_received >= $i->quantity)) {
                $po->update(['status' => 'Delivered', 'received_at' => now()]);
            }

            FinancialEntry::create([
                'entry_date'     => today(),
                'type'           => 'expense',
                'amount'         => round($cost, 2),
                'description'    => "Delivery - PO #{$po->id}",
                'reference_type' => 'PO',
                'reference_id'   => (string) $po->id,
            ]);

            return $po;
        });
    }
}
