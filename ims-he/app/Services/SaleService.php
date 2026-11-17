<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private InventoryService $inventory,
        private LedgerService $ledger,
    ) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int}>  $lines
     */
    public function sell(
        array $lines,
        string $method,
        float $tendered,
        User $cashier,
        ?Customer $customer = null,
    ): Invoice {
        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one product.']);
        }

        if (! in_array($method, ['Cash', 'GCash'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Choose Cash or GCash.']);
        }

        return DB::transaction(function () use ($lines, $method, $tendered, $cashier, $customer): Invoice {
            $quantities = [];

            foreach ($lines as $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $quantity = (int) ($line['quantity'] ?? 0);

                if ($productId < 1 || $quantity < 1) {
                    throw ValidationException::withMessages(['items' => 'Each sale line must have a valid product and quantity.']);
                }

                $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
            }

            $invoice = Invoice::create([
                'invoice_date' => today(),
                'customer_id' => $customer?->customer_id,
                'employee_id' => $cashier->employee_id,
                'channel' => 'In-store',
                'status' => 'Completed',
                'total_amount' => 0,
            ]);

            $total = 0.0;

            foreach ($quantities as $productId => $quantity) {
                $product = Product::findOrFail($productId);
                $price = (float) $product->unit_price;
                $subtotal = round($price * $quantity, 2);

                $invoice->items()->create([
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $this->inventory->deduct($product->product_id, $quantity, 'Invoice', $invoice->invoice_id);
                $total += $subtotal;
            }

            $total = round($total, 2);
            $tendered = round($tendered, 2);

            if ($tendered < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Amount paid is less than the total.']);
            }

            $invoice->update(['total_amount' => $total]);

            Payment::create([
                'invoice_id' => $invoice->invoice_id,
                'payment_date' => today(),
                'amount_paid' => $tendered,
                'change_amount' => round($tendered - $total, 2),
                'payment_method' => $method,
            ]);

            $this->ledger->recordSale($invoice);

            return $invoice->load('items.product', 'payment', 'customer', 'employee');
        });
    }
}
