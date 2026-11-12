<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Checkout part of the Cart & Checkout Module (SDD 4.1, UC20).
// Everything happens in ONE database transaction: if any step fails (for example the stock changed while the
// customer was paying), nothing is saved and the cart stays as it was.
class CheckoutService
{
    public const PAYMENT_METHODS = ['Cash', 'GCash'];     // SDD 7.2.14. No online payment gateway yet (SDD 1.2).

    public function __construct(private CartService $carts, private InventoryService $inventory) {}

    /**
     * @param array{first_name:string,last_name:string,contact_number:string,address:string} $details
     */
    public function placeOrder(Customer $customer, array $details, string $paymentMethod): Invoice
    {
        return DB::transaction(function () use ($customer, $details, $paymentMethod) {
            $cart = $this->carts->cartFor($customer);
            $items = $cart->items()->with('product')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            // UC20 4a: report every line that cannot be bought, so the customer can fix the cart in one go.
            $problems = [];
            foreach ($items as $item) {
                $product = $item->product;

                if (! $product->is_active) {
                    $problems[] = "{$product->product_name} is no longer available.";
                } elseif ($item->quantity > $product->stock_quantity) {
                    $problems[] = $product->isInStock()
                        ? "Only {$product->stock_quantity} of {$product->product_name} in stock."
                        : "{$product->product_name} is out of stock.";
                }
            }
            if ($problems) {
                throw ValidationException::withMessages(['cart' => $problems]);
            }

            $customer->update($details);              // delivery details become the profile

            $invoice = Invoice::create([
                'invoice_date' => today(),
                'customer_id' => $customer->customer_id,
                'employee_id' => null,                // online order: no employee involved
                'channel' => 'Online',
                'status' => 'Pending',                // staff confirm and fulfil the order later
                'total_amount' => 0,
            ]);

            $total = 0.0;
            foreach ($items as $item) {
                $price = (float) $item->product->unit_price;      // price at the time of the order (SDD 7.2.13)
                $subtotal = round($price * $item->quantity, 2);

                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);

                // Locks the product row, re-checks the stock and writes the OUT movement (SDD 4.2).
                $this->inventory->deduct($item->product_id, $item->quantity, 'Invoice', $invoice->invoice_id);

                $total += $subtotal;
            }

            $total = round($total, 2);
            $invoice->update(['total_amount' => $total]);

            Payment::create([
                'invoice_id' => $invoice->invoice_id,
                'payment_date' => today(),
                'amount_paid' => $total,
                'change_amount' => 0,
                'payment_method' => $paymentMethod,
            ]);

            $cart->items()->delete();
            $cart->touch();

            return $invoice->load('items.product', 'payment', 'customer');
        });
    }
}
