<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// UC20 Checkout. Only logged-in customers reach it (auth:customer, UC20 includes UC01).
class CheckoutController extends Controller
{
    public function __construct(private CartService $carts, private CheckoutService $checkout) {}

    public function show(Request $request)
    {
        $customer = $request->user('customer');
        $items = $this->carts->cartFor($customer)->items()->with('product')->get()->sortBy('product.product_name');

        if ($items->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('status', 'Your cart is empty. Add something before checking out.');
        }

        return view('checkout.show', [
            'customer' => $customer,
            'items' => $items,
            'total' => $items->sum(fn (CartItem $i) => $i->quantity * $i->product->unit_price),
            'methods' => CheckoutService::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
            'contact_number' => ['required', 'regex:/^[0-9]+$/', 'min:7', 'max:14'],
            'address' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', Rule::in(CheckoutService::PAYMENT_METHODS)],
        ], [
            'first_name.regex' => 'First name may only contain letters and spaces.',
            'last_name.regex' => 'Last name may only contain letters and spaces.',
            'contact_number.regex' => 'Contact number may only contain digits.',
            'payment_method.in' => 'Please choose a valid payment method.',
        ]);

        $invoice = $this->checkout->placeOrder(
            $request->user('customer'),
            collect($data)->only(['first_name', 'last_name', 'contact_number', 'address'])->all(),
            $data['payment_method'],
        );

        return redirect()->route('customer.orders.show', $invoice)->with('status', 'Order placed. Thank you!');
    }
}
