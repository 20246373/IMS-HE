<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();
        $cart = $customer->cart;

        if (!$cart) {
            $cart = $customer->carts()->create(['status' => 'Draft']);
        }

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $customer = auth('customer')->user();
        $cart = $customer->cart;

        if (!$cart) {
            $cart = $customer->carts()->create(['status' => 'Draft']);
        }

        $product = Product::findOrFail($request->product_id);

        if ($request->quantity > $product->stock_quantity) {
            return back()->with('error', 'Not enough stock available.');
        }

        $cartItem = $cart->items()->where('product_id', $product->product_id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($newQuantity > $product->stock_quantity) {
                return back()->with('error', 'Not enough stock available.');
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create([
                'product_id' => $product->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        $cart->touch();

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $this->authorizeCartAccess($cartItem);

        $product = $cartItem->product;

        if ($request->quantity > $product->stock_quantity) {
            return back()->with('error', 'Not enough stock available.');
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->cart->touch();

        return back()->with('status', 'Cart updated.');
    }

    public function remove(CartItem $cartItem)
    {
        $this->authorizeCartAccess($cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->touch();

        return back()->with('status', 'Item removed from cart.');
    }

    public function clear()
    {
        $customer = auth('customer')->user();
        $cart = $customer->cart;

        if ($cart) {
            $cart->items()->delete();
            $cart->touch();
        }

        return back()->with('status', 'Cart cleared.');
    }

    public function checkout()
    {
        $customer = auth('customer')->user();
        $cart = $customer->cart;

        if (!$cart || $cart->items()->count() === 0) {
            return redirect()->route('customer.cart.index')->with('error', 'Your cart is empty.');
        }

        return view('cart.checkout', compact('cart'));
    }

    public function processCheckout(Request $request)
    {
        $customer = auth('customer')->user();
        $cart = $customer->cart;

        if (!$cart || $cart->items()->count() === 0) {
            return back()->with('error', 'Your cart is empty.');
        }

        DB::transaction(function () use ($customer, $cart) {
            foreach ($cart->items as $item) {
                $product = $item->product;

                if ($item->quantity > $product->stock_quantity) {
                    throw new \Exception("Not enough stock for {$product->product_name}.");
                }
            }

            $cart->update(['status' => 'Submitted']);

            $customer->carts()->create([
                'status' => 'Draft',
            ]);
        });

        return redirect()->route('customer.cart.index')->with('status', 'Order submitted successfully! Please wait for a clerk to process your order at the counter.');
    }

    public function orders()
    {
        $customer = auth('customer')->user();
        $submittedCarts = $customer->submittedCarts()->with('items.product')->latest()->paginate(10);

        return view('cart.orders', compact('submittedCarts'));
    }

    public function showOrder(Cart $cart)
    {
        $customer = auth('customer')->user();

        if ($cart->customer_id !== $customer->customer_id) {
            abort(403);
        }

        $cart->load('items.product');

        return view('cart.order', compact('cart'));
    }

    private function authorizeCartAccess(CartItem $cartItem)
    {
        $customer = auth('customer')->user();

        if ($cartItem->cart->customer_id !== $customer->customer_id) {
            abort(403);
        }
    }
}
