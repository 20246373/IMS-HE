<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Cart part of the Cart & Checkout Module (SDD 4.1). Checkout lives in CheckoutService.
// One cart per customer (carts.customer_id unique) and one line per product (cart_items unique cart_id + product_id).
class CartService
{
    // Session key holding the price each product had when the customer last saw it (product_id => price).
    // cart_items has no price column (SDD 7.2.17) and the schema is frozen, so the snapshot lives in the session.
    public const PRICE_SNAPSHOT_KEY = 'cart.seen_prices';

    public function cartFor(Customer $customer): Cart
    {
        return Cart::firstOrCreate(['customer_id' => $customer->customer_id]);
    }

    /**
     * Brings the cart in line with current stock and prices before it is shown or checked out:
     * - a line above the available stock is clamped down to it (or removed when the product sold out);
     * - a price that changed since the customer added or last saw the item is reported once.
     *
     * @return list<string> warnings for the customer
     */
    public function reconcile(Cart $cart): array
    {
        $warnings = [];
        $seen = session(self::PRICE_SNAPSHOT_KEY, []);

        DB::transaction(function () use ($cart, &$warnings, &$seen) {
            foreach ($cart->items()->with('product')->get() as $item) {
                $product = $item->product;

                if ($item->quantity > $product->stock_quantity) {
                    if ($product->isInStock()) {
                        $item->update(['quantity' => $product->stock_quantity]);
                        $warnings[] = "Only {$product->stock_quantity} of {$product->product_name} left, so your quantity was changed to {$product->stock_quantity}.";
                    } else {
                        $item->delete();
                        $warnings[] = "{$product->product_name} is out of stock and was removed from your cart.";
                        unset($seen[$product->product_id]);
                        continue;
                    }
                }

                $price = number_format((float) $product->unit_price, 2, '.', '');
                $before = $seen[$product->product_id] ?? null;
                if ($before !== null && number_format((float) $before, 2, '.', '') !== $price) {
                    $warnings[] = "The price of {$product->product_name} changed from ".money($before).' to '.money($price).'.';
                }
                $seen[$product->product_id] = $price;
            }

            if ($warnings) {
                $cart->touch();
            }
        });

        session([self::PRICE_SNAPSHOT_KEY => $seen]);

        return $warnings;
    }

    // Adding a product already in the cart raises that line's quantity instead of adding a second line.
    public function add(Customer $customer, Product $product, int $quantity): CartItem
    {
        return DB::transaction(function () use ($customer, $product, $quantity) {
            $cart = $this->cartFor($customer);
            $item = $cart->items()->firstOrNew(['product_id' => $product->product_id]);
            $newQty = ($item->exists ? $item->quantity : 0) + $quantity;

            $this->assertSellable($product, $newQty);

            $item->quantity = $newQty;
            $item->save();
            $cart->touch();

            // Remember the price the customer saw when adding, so a later change can be flagged in the cart.
            session()->put(self::PRICE_SNAPSHOT_KEY.'.'.$product->product_id, number_format((float) $product->unit_price, 2, '.', ''));

            return $item;
        });
    }

    public function update(CartItem $item, int $quantity): void
    {
        $this->assertSellable($item->product, $quantity);

        $item->update(['quantity' => $quantity]);
        $item->cart->touch();
    }

    public function remove(CartItem $item): void
    {
        $cart = $item->cart;
        session()->forget(self::PRICE_SNAPSHOT_KEY.'.'.$item->product_id);
        $item->delete();
        $cart->touch();
    }

    // Quantity must not exceed available stock (SDD 7.2.17).
    private function assertSellable(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => $product->isInStock()
                ? "Only {$product->stock_quantity} of {$product->product_name} in stock."
                : "{$product->product_name} is out of stock."]);
        }
    }
}
