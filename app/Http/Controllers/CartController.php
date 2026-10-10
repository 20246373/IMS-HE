<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Customer cart (no checkout yet - that is the second half).
 * Routes sit behind auth + role:customer, so guests are sent to /login
 * and then back to the page they came from (UC19 includes UC01).
 */
class CartController extends Controller
{
    private function cart(Request $request): Cart
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403, 'No customer profile for this account.');

        return Cart::firstOrCreate(['customer_id' => $customer->id]); // one cart per customer
    }

    public function index(Request $request)
    {
        $cart = $this->cart($request)->load('items.product');

        return view('cart.index', ['cart' => $cart]);
    }

    public function add(Request $request)
    {
        $d = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($d['product_id']);
        if (! $product->is_active || $product->stock_quantity < 1) {
            return back()->withErrors(['quantity' => "{$product->name} is out of stock."]);
        }

        $cart = $this->cart($request);
        $item = $cart->items()->where('product_id', $product->id)->first(); // unique product line
        $newQty = ($item?->quantity ?? 0) + (int) $d['quantity'];

        if ($newQty > $product->stock_quantity) {
            return back()->withErrors(['quantity' => "Only {$product->stock_quantity} of {$product->name} in stock (you already have " . ($item?->quantity ?? 0) . ' in your cart).']);
        }

        $cart->items()->updateOrCreate(['product_id' => $product->id], ['quantity' => $newQty]);

        return redirect()->route('cart.index')->with('status', "{$product->name} added to cart.");
    }

    public function update(Request $request, CartItem $item)
    {
        $this->ownItem($request, $item);
        $d = $request->validate(['quantity' => 'required|integer|min:1']);

        $stock = $item->product->stock_quantity;
        if ($d['quantity'] > $stock) {
            return back()->withErrors(['quantity' => "Only {$stock} of {$item->product->name} in stock."]);
        }

        $item->update(['quantity' => $d['quantity']]);

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, CartItem $item)
    {
        $this->ownItem($request, $item);
        $item->delete();

        return back()->with('status', 'Item removed.');
    }

    private function ownItem(Request $request, CartItem $item): void
    {
        abort_unless($item->cart_id === $this->cart($request)->id, 403);
    }
}
