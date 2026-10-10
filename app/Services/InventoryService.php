<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ONLY place that should change products.stock_quantity.
 * Every call also writes an inventory_transactions row (OUT or IN).
 *
 *   app(InventoryService::class)->deduct($productId, 3, 'Invoice', $invoice->id);
 *   app(InventoryService::class)->add($productId, 20, 'PO', $po->id);
 *
 * reference_type values used so far: Invoice, PO, Adjustment.
 * Both methods join an outer DB::transaction if the caller already opened one.
 * Throws ValidationException (message under "items") on bad qty or if stock would go negative.
 */
class InventoryService
{
    public function deduct(int $productId, int $qty, string $referenceType, string|int|null $referenceId = null): Product
    {
        return $this->move('OUT', $productId, $qty, $referenceType, $referenceId);
    }

    public function add(int $productId, int $qty, string $referenceType, string|int|null $referenceId = null): Product
    {
        return $this->move('IN', $productId, $qty, $referenceType, $referenceId);
    }

    private function move(string $type, int $productId, int $qty, string $referenceType, string|int|null $referenceId): Product
    {
        return DB::transaction(function () use ($type, $productId, $qty, $referenceType, $referenceId) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            if ($qty < 1) {
                throw ValidationException::withMessages(['items' => "Invalid quantity for {$product->name}."]);
            }

            if ($type === 'OUT') {
                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name} (available: {$product->stock_quantity}).",
                    ]);
                }
                $product->decrement('stock_quantity', $qty);
            } else {
                $product->increment('stock_quantity', $qty);
            }

            InventoryTransaction::create([
                'product_id'       => $product->id,
                'type'             => $type,
                'quantity'         => $qty,
                'transaction_date' => today(),
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId === null ? null : (string) $referenceId,
            ]);

            return $product->refresh();
        });
    }
}
