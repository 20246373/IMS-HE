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
 *   app(InventoryService::class)->deduct($productId, 3, 'Invoice', $invoice->invoice_id);
 *   app(InventoryService::class)->add($productId, 20, 'PO', $po->po_id);
 *
 * reference_type values used so far: Invoice, PO, Adjustment.
 * Both methods join an outer DB::transaction if the caller already opened one.
 * Throws ValidationException (message under "items") on invalid movements or if stock
 * would become negative or exceed the database column's unsigned limit.
 */
class InventoryService
{
    public function deduct(int $productId, int $qty, string $referenceType, int $referenceId): Product
    {
        return $this->move('OUT', $productId, $qty, $referenceType, $referenceId);
    }

    public function add(int $productId, int $qty, string $referenceType, int $referenceId): Product
    {
        return $this->move('IN', $productId, $qty, $referenceType, $referenceId);
    }

    private function move(string $type, int $productId, int $qty, string $referenceType, int $referenceId): Product
    {
        return DB::transaction(function () use ($type, $productId, $qty, $referenceType, $referenceId) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            if (
                $qty < 1
                || trim($referenceType) === ''
                || strlen($referenceType) > 30
                || $referenceId < 1
                || $referenceId > 4294967295
            ) {
                throw ValidationException::withMessages([
                    'items' => "Invalid inventory movement for {$product->product_name}.",
                ]);
            }

            if ($type === 'OUT') {
                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->product_name} (available: {$product->stock_quantity}).",
                    ]);
                }
                $product->stock_quantity -= $qty;
            } else {
                if ($qty > 4294967295 - (int) $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Stock quantity limit exceeded for {$product->product_name}.",
                    ]);
                }

                $product->stock_quantity += $qty;
            }

            $product->save();

            InventoryTransaction::create([
                'product_id' => $product->product_id,
                'transaction_type' => $type,
                'quantity' => $qty,
                'transaction_date' => today(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $product->refresh();
        });
    }
}
