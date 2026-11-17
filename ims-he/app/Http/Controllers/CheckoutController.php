<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Customer;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// UC20 Checkout. Only logged-in customers reach it (auth:customer, UC20 includes UC01).
// Registration is email + password only (defense comments 1-2), so name, contact number and address are asked
// for here, and only the ones still missing from the customer's profile.
class CheckoutController extends Controller
{
    private const RULES = [
        'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
        'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
        'contact_number' => ['required', 'regex:/^[0-9]+$/', 'min:7', 'max:14'],
        'address' => ['required', 'string', 'max:100'],
    ];

    private const MESSAGES = [
        'first_name.regex' => 'First name may only contain letters and spaces.',
        'last_name.regex' => 'Last name may only contain letters and spaces.',
        'contact_number.regex' => 'Contact number may only contain digits.',
        'payment_method.in' => 'Please choose a valid payment method.',
    ];

    public function __construct(private CartService $carts, private CheckoutService $checkout) {}

    public function show(Request $request)
    {
        $customer = $request->user('customer');
        $cart = $this->carts->cartFor($customer);

        // Same stock clamp and price-change check as the cart page, so the summary is accurate.
        $warnings = $this->carts->reconcile($cart);
        $items = $cart->items()->with('product')->get()->sortBy('product.product_name');

        if ($items->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('status', 'Your cart is empty. Add something before checking out.');
        }

        return view('checkout.show', [
            'customer' => $customer,
            'missing' => $this->missingDetails($customer),
            'items' => $items,
            'warnings' => $warnings,
            'total' => $items->sum(fn (CartItem $i) => $i->quantity * $i->product->unit_price),
            'methods' => CheckoutService::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $customer = $request->user('customer');
        $missing = $this->missingDetails($customer);

        // Only the missing details are required (and accepted); saved ones are reused as they are.
        $data = $request->validate(
            collect(self::RULES)->only($missing)->all()
                + ['payment_method' => ['required', Rule::in(CheckoutService::PAYMENT_METHODS)]],
            self::MESSAGES,
        );

        $details = collect(array_keys(self::RULES))
            ->mapWithKeys(fn ($field) => [$field => $data[$field] ?? $customer->{$field}])
            ->all();

        $invoice = $this->checkout->placeOrder($customer, $details, $data['payment_method']);

        // Stock deduction can flash a staff-only low-stock/reorder warning (ProcurementService); customers must not see it.
        $request->session()->forget('warning');

        return redirect()->route('customer.orders.confirmation', $invoice);
    }

    /** @return list<string> profile fields that are still empty */
    private function missingDetails(Customer $customer): array
    {
        return array_values(array_filter(
            array_keys(self::RULES),
            fn ($field) => trim((string) $customer->{$field}) === '',
        ));
    }
}
