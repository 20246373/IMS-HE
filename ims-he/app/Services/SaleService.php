<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * @param array<int, array{product_id:int, quantity:int}> $items
     * Verifies stock, records the invoice, auto-deducts inventory, records payment + ledger entry.
     * Everything runs in one transaction, so a failure rolls the whole sale back (SRS 4.3.d).
     */
    public function checkout(array $items, float $amountPaid, string $method, ?int $customerId, ?int $userId): Invoice
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($items, $amountPaid, $method, $customerId, $userId) {
            $invoice = Invoice::create([
                'invoice_date' => today(),
                'customer_id'  => $customerId,
                'user_id'      => $userId,
                'total_amount' => 0,
            ]);

            $total = 0.0;
            foreach ($items as $row) {
                $qty = (int) $row['quantity'];
                $product = Product::lockForUpdate()->findOrFail($row['product_id']);

                if ($qty < 1) {
                    throw ValidationException::withMessages(['items' => "Invalid quantity for {$product->name}."]);
                }
                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['items' => "Insufficient stock for {$product->name} (available: {$product->stock_quantity})."]);
                }

                $price = (float) $product->unit_price;
                $subtotal = round($price * $qty, 2);

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'price'      => $price,
                    'subtotal'   => $subtotal,
                ]);

                $product->decrement('stock_quantity', $qty);

                InventoryTransaction::create([
                    'product_id'       => $product->id,
                    'type'             => 'OUT',
                    'quantity'         => $qty,
                    'transaction_date' => today(),
                    'reference_type'   => 'Invoice',
                    'reference_id'     => (string) $invoice->id,
                ]);

                $total += $subtotal;
            }

            $total = round($total, 2);
            if ($amountPaid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Amount paid is less than the total.']);
            }

            $invoice->update(['total_amount' => $total]);

            Payment::create([
                'invoice_id'     => $invoice->id,
                'payment_date'   => today(),
                'amount_paid'    => $amountPaid,
                'change_amount'  => round($amountPaid - $total, 2),
                'payment_method' => $method,
            ]);

            FinancialEntry::create([
                'entry_date'     => today(),
                'type'           => 'income',
                'amount'         => $total,
                'description'    => "Sale - Invoice #{$invoice->id}",
                'reference_type' => 'Invoice',
                'reference_id'   => (string) $invoice->id,
            ]);

            return $invoice->load('items.product', 'payment');
        });
    }
}
