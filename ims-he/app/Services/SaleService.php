<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * @param array<int, array{product_id:int, quantity:int}> $items
     * Verifies stock, records the invoice, auto-deducts inventory, records payment.
     * Everything runs in one transaction, so a failure rolls the whole sale back (SRS 4.3.d).
     */
    public function checkout(array $items, float $amountPaid, string $method, ?int $customerId, ?int $userId): Invoice
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        // Merge duplicate product lines so each product is checked and deducted once.
        $lines = [];
        foreach ($items as $row) {
            $productId = (int) $row['product_id'];
            $lines[$productId] = ($lines[$productId] ?? 0) + (int) $row['quantity'];
        }

        $employeeId = $userId ? User::find($userId)?->employee_id : null;

        return DB::transaction(function () use ($lines, $amountPaid, $method, $customerId, $employeeId) {
            // Lock every product and verify stock + compute the total before any row is written.
            $verified = [];
            $total = 0.0;
            foreach ($lines as $productId => $qty) {
                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($qty < 1) {
                    throw ValidationException::withMessages(['items' => "Invalid quantity for {$product->product_name}."]);
                }
                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['items' => "Insufficient stock for {$product->product_name} (available: {$product->stock_quantity})."]);
                }

                $price = (float) $product->unit_price;
                $subtotal = round($price * $qty, 2);
                $verified[] = ['product' => $product, 'quantity' => $qty, 'price' => $price, 'subtotal' => $subtotal];
                $total += $subtotal;
            }
            $total = round($total, 2);

            if ($amountPaid < $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Amount paid is less than the total.']);
            }

            $invoice = Invoice::create([
                'invoice_date' => today(),
                'customer_id'  => $customerId,
                'employee_id'  => $employeeId,
                'channel'      => 'In-store',
                'status'       => 'Completed',
                'total_amount' => $total,
            ]);

            foreach ($verified as $line) {
                $invoice->items()->create([
                    'product_id' => $line['product']->product_id,
                    'quantity'   => $line['quantity'],
                    'price'      => $line['price'],
                    'subtotal'   => $line['subtotal'],
                ]);

                $line['product']->decrement('stock_quantity', $line['quantity']);

                InventoryTransaction::create([
                    'product_id'       => $line['product']->product_id,
                    'transaction_type' => 'OUT',
                    'quantity'         => $line['quantity'],
                    'transaction_date' => today(),
                    'reference_type'   => 'Invoice',
                    'reference_id'     => $invoice->invoice_id,
                ]);
            }

            Payment::create([
                'invoice_id'     => $invoice->invoice_id,
                'payment_date'   => today(),
                'amount_paid'    => $amountPaid,
                'change_amount'  => round($amountPaid - $total, 2),
                'payment_method' => $method,
            ]);

            AuditLog::record('Completed', 'invoices', $invoice->invoice_id);

            return $invoice->load('items.product', 'payment');
        });
    }
}
