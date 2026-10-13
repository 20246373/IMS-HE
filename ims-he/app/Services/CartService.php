<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Cart part of the Cart & Checkout Module (SDD 4.1). Checkout is not built yet.
// One cart per customer (carts.customer_id unique) and one line per product (cart_items unique cart_id + product_id).
class CartService
{
    public function cartFor(Customer $customer): Cart
    {
        return Cart::firstOrCreate(['customer_id' => $customer->customer_id]);
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
        $item->delete();
        $cart->touch();
    }

    // Quantity must not exceed available stock (SDD 7.2.17).
    private function assertSellable(Product $product, int $quantity): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['quantity' => "{$product->product_name} is no longer available."]);
        }

        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => $product->isInStock()
                ? "Only {$product->stock_quantity} of {$product->product_name} in stock."
                : "{$product->product_name} is out of stock."]);
        }
    }
}
