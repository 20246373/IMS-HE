<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
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
                Product::lockForUpdate()->findOrFail($item->product_id)->increment('stock_quantity', $item->quantity);

                InventoryTransaction::create([
                    'product_id'       => $item->product_id,
                    'type'             => 'IN',
                    'quantity'         => $item->quantity,
                    'transaction_date' => today(),
                    'reference_type'   => 'PO',
                    'reference_id'     => (string) $po->id,
                ]);
                $cost += (float) $item->subtotal;
            }

            $po->update(['status' => 'Delivered', 'received_at' => now()]);

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
