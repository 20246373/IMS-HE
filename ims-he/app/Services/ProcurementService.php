<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(private InventoryService $inventory) {}

    /** Confirms a delivery against its PO: adds stock, logs IN movements, marks PO Delivered. */
    public function receive(PurchaseOrder $po): PurchaseOrder
    {
        return DB::transaction(function () use ($po) {
            $po = PurchaseOrder::lockForUpdate()->with('items')->findOrFail($po->id);

            if ($po->status !== 'Pending') {
                throw ValidationException::withMessages(['status' => "PO #{$po->id} is already {$po->status}."]);
            }

            $cost = 0.0;
            foreach ($po->items as $item) {
                $this->inventory->add(
                    (int) $item->product_id,
                    (int) $item->quantity,
                    'PO',
                    (int) $po->po_id,
                );
                $cost += (float) $item->subtotal;
            }

            $po->update(['status' => 'Delivered', 'received_at' => now()]);

            FinancialEntry::create([
                'entry_date' => today(),
                'type' => 'expense',
                'amount' => round($cost, 2),
                'description' => "Delivery - PO #{$po->id}",
                'reference_type' => 'PO',
                'reference_id' => (string) $po->id,
            ]);

            return $po;
        });
    }
}
