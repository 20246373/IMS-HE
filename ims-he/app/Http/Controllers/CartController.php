<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');
        $cart = $customer->cart;

        if ($cart === null) {
            return view('cart.index', ['cart' => null, 'items' => collect(), 'total' => 0]);
        }

        $items = $cart->items()->with('product')->get();
        $total = $items->sum(fn ($item) => $item->quantity * $item->product->unit_price);

        return view('cart.index', ['cart' => $cart, 'items' => $items, 'total' => $total]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        $customer = $request->user('customer');

        $result = DB::transaction(function () use ($customer, $data) {
            $product = Product::whereKey($data['product_id'])->firstOrFail();

            if (! $product->is_active) {
                throw new \Exception('This product is not available.');
            }

            if ($product->stock_quantity < $data['quantity']) {
                throw new \Exception("Only {$product->stock_quantity} available in stock.");
            }

            $cart = $customer->cart;
            if ($cart === null) {
                $cart = Cart::create([
                    'customer_id' => $customer->customer_id,
                    'updated_at' => now(),
                ]);
            }

            $existingItem = $cart->items()->where('product_id', $product->product_id)->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $data['quantity'];
                $available = $product->stock_quantity - $existingItem->quantity;
                if ($newQuantity > $product->stock_quantity) {
                    throw new \Exception("Cannot add {$data['quantity']} more; only {$available} available in stock.");
                }
                $existingItem->update(['quantity' => $newQuantity]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->cart_id,
                    'product_id' => $product->product_id,
                    'quantity' => $data['quantity'],
                ]);
            }

            $cart->update(['updated_at' => now()]);

            return $cart;
        });

        return back()->with('status', 'Added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        DB::transaction(function () use ($customer, $cartItem, $data) {
            $cart = $customer->cart;
            if ($cart === null || $cart->cart_id !== $cartItem->cart_id) {
                abort(403);
            }

            $cartItem->load('product');

            if ($data['quantity'] > $cartItem->product->stock_quantity) {
                throw new \Exception("Only {$cartItem->product->stock_quantity} available in stock.");
            }

            $cartItem->update(['quantity' => $data['quantity']]);
            $cart->update(['updated_at' => now()]);
        });

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, CartItem $cartItem): RedirectResponse
    {
        $customer = $request->user('customer');

        DB::transaction(function () use ($customer, $cartItem) {
            $cart = $customer->cart;
            if ($cart === null || $cart->cart_id !== $cartItem->cart_id) {
                abort(403);
            }

            $cartItem->delete();
            $cart->update(['updated_at' => now()]);
        });

        return back()->with('status', 'Item removed from cart.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        DB::transaction(function () use ($customer) {
            $cart = $customer->cart;
            if ($cart === null) {
                return;
            }

            $cart->items()->delete();
            $cart->update(['updated_at' => now()]);
        });

        return back()->with('status', 'Cart cleared.');
    }
}
