<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @param array<int, array{product_id:int, quantity:int}> $items
     * One DB transaction: invoice -> items -> stock deduction -> payment -> ledger entry.
     * Any failure (stock, payment too low, DB error) rolls the whole sale back.
     */
    public function checkout(array $items, float $amountPaid, string $method, ?int $customerId, ?int $userId, string $channel = 'In-store'): Invoice
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($items, $amountPaid, $method, $customerId, $userId, $channel) {
            $invoice = Invoice::create([
                'invoice_date' => today(),
                'customer_id'  => $customerId,
                'user_id'      => $userId,
                'channel'      => $channel,
                'total_amount' => 0,
            ]);

            $total = 0.0;
            foreach ($items as $row) {
                $qty = (int) $row['quantity'];
                $product = Product::findOrFail($row['product_id']);

                if (! $product->is_active) {
                    throw ValidationException::withMessages(['items' => "{$product->name} is not available for sale."]);
                }

                // Deducts stock + logs the OUT movement; throws if stock would go negative.
                $product = $this->inventory->deduct($product->id, $qty, 'Invoice', $invoice->id);

                $price = (float) $product->unit_price;
                $subtotal = round($price * $qty, 2);

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'price'      => $price,   // unit_price copied at time of sale
                    'subtotal'   => $subtotal,
                ]);

                $total += $subtotal;
            }

            $total = round($total, 2);
            if ($amountPaid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Amount paid must be at least the total (₱' . number_format($total, 2) . ').']);
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
