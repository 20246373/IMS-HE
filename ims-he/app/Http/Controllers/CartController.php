<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

// Customer cart (SDD 4.1 Cart & Checkout Module, cart part only). Guests are sent to login by auth:customer (UC19 includes UC01).
class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function index(Request $request)
    {
        $cart = $this->carts->cartFor($request->user('customer'))->load('items.product');

        // Totals are recalculated from current prices on every view.
        $items = $cart->items->sortBy('product.product_name');
        $total = $items->sum(fn (CartItem $i) => $i->quantity * $i->product->unit_price);

        return view('cart.index', ['items' => $items, 'total' => $total]);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:9999']);

        $this->carts->add($request->user('customer'), $product, $data['quantity']);

        return redirect()->route('customer.cart.index')->with('status', "{$product->product_name} added to your cart.");
    }

    public function update(Request $request, CartItem $item)
    {
        $this->authorizeItem($request, $item);
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:9999']);

        $this->carts->update($item, $data['quantity']);

        // The cart page saves - / + changes in the background and only needs the recalculated numbers back.
        if ($request->expectsJson()) {
            $items = $item->cart->items()->with('product')->get();

            return response()->json([
                'quantity' => $item->quantity,
                'subtotal' => $item->quantity * $item->product->unit_price,
                'total' => $items->sum(fn (CartItem $i) => $i->quantity * $i->product->unit_price),
                'count' => $items->sum('quantity'),
            ]);
        }

        return redirect()->route('customer.cart.index')->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $item)
    {
        $this->authorizeItem($request, $item);

        $this->carts->remove($item);

        return redirect()->route('customer.cart.index')->with('status', 'Item removed from your cart.');
    }

    // A customer can only change lines in their own cart.
    private function authorizeItem(Request $request, CartItem $item): void
    {
        abort_unless((int) $item->cart->customer_id === (int) $request->user('customer')->customer_id, 404);
    }
}
