<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

// UC20 Checkout: order summary, validation, one-transaction order creation, receipt and order history.
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = Category::create(['category_name' => 'Hand Tools']);

        // These tests place many orders quickly; the checkout/login rate limits are not what is being tested here.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function customer(string $email = 'juan@example.com'): Customer
    {
        return Customer::create(['email' => $email, 'password' => 'secret123', 'email_verified_at' => now()]);
    }

    private function product(string $name = 'Claw Hammer', int $stock = 10, float $price = 350, bool $active = true): Product
    {
        return Product::create([
            'category_id' => $this->cat->category_id, 'product_name' => $name, 'unit_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => 0, 'is_active' => $active,
        ]);
    }

    private function inCart(Customer $customer, Product $product, int $qty): void
    {
        app(CartService::class)->add($customer, $product, $qty);
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'contact_number' => '09171234567',
            'address' => 'Baguio City', 'payment_method' => 'Cash', 'fulfillment' => 'Delivery',
        ], $overrides);
    }

    public function test_guests_must_log_in_first(): void
    {
        $this->get(route('customer.checkout.show'))->assertRedirect(route('login', ['as' => 'customer']));
        $this->post(route('customer.checkout.store'), $this->details())->assertRedirect(route('login', ['as' => 'customer']));
    }

    public function test_an_empty_cart_cannot_check_out(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))->assertRedirect(route('customer.cart.index'));

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_checkout_page_shows_the_order_summary_and_payment_methods(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product('Claw Hammer', 10, 350), 2);

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))
            ->assertOk()->assertSee('Claw Hammer')->assertSee('700.00')->assertSee('Cash')->assertSee('GCash');
    }

    public function test_resubmitting_checkout_does_not_create_a_second_order_or_deduct_stock_again(): void
    {
        $customer = $this->customer();
        $product = $this->product(stock: 2);
        $this->inCart($customer, $product, 2);
        $this->actingAs($customer, 'customer');

        $checkout = route('customer.checkout.store');
        $this->post($checkout, $this->details())
            ->assertRedirect(route('customer.orders.confirmation', Invoice::firstOrFail()));
        $this->post($checkout, $this->details())->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertSame(0, (int) $product->fresh()->stock_quantity);
        $this->assertSame(0, $customer->cart->items()->count());
    }

    public function test_cart_page_has_a_checkout_button(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->get(route('customer.cart.index'))->assertSee(route('customer.checkout.show'));
    }

    public function test_placing_an_order_creates_the_records_deducts_stock_and_clears_the_cart(): void
    {
        $customer = $this->customer();
        $hammer = $this->product('Claw Hammer', 10, 350);
        $drill = $this->product('Drill', 5, 120);
        $this->inCart($customer, $hammer, 2);
        $this->inCart($customer, $drill, 1);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details(['payment_method' => 'GCash']));

        $invoice = Invoice::firstOrFail();
        $this->assertEquals(820.00, (float) $invoice->total_amount);
        $this->assertSame('Online', $invoice->channel);
        $this->assertSame('Pending', $invoice->status);
        $this->assertSame($customer->customer_id, (int) $invoice->customer_id);
        $this->assertNull($invoice->employee_id);

        $this->assertDatabaseCount('invoice_items', 2);
        $line = $invoice->items()->where('product_id', $hammer->product_id)->firstOrFail();
        $this->assertSame(2, (int) $line->quantity);
        $this->assertEquals(350.00, (float) $line->price);
        $this->assertEquals(700.00, (float) $line->subtotal);

        $payment = $invoice->payment;
        $this->assertSame('GCash', $payment->payment_method);
        $this->assertEquals(820.00, (float) $payment->amount_paid);

        $this->assertSame(8, (int) $hammer->fresh()->stock_quantity);
        $this->assertSame(4, (int) $drill->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $hammer->product_id, 'transaction_type' => 'OUT', 'quantity' => 2,
            'reference_type' => 'Invoice', 'reference_id' => $invoice->invoice_id,
        ]);

        $this->assertSame(0, $customer->cart->items()->count());

        $customer->refresh();
        $this->assertSame('Juan', $customer->first_name);
        $this->assertSame('Baguio City', $customer->address);
    }

    public function test_the_customer_lands_on_the_order_confirmation(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product('Claw Hammer', 10, 1234.5), 1);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())
            ->assertRedirect(route('customer.orders.confirmation', $invoice = Invoice::firstOrFail()));

        $this->actingAs($customer, 'customer')->get(route('customer.orders.confirmation', $invoice))
            ->assertOk()->assertSee('Order Successfully Placed!')->assertSee('#'.$invoice->invoice_id)
            ->assertSee('₱1,234.50')->assertSee('Pending')->assertSee(route('customer.orders.show', $invoice));
    }

    public function test_missing_details_or_a_bad_payment_method_save_nothing(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->inCart($customer, $product, 2);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details(['address' => '', 'contact_number' => 'abc', 'payment_method' => 'Bitcoin']))
            ->assertSessionHasErrors(['address', 'contact_number', 'payment_method']);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, $customer->cart->items()->count());
    }

    public function test_stock_that_ran_out_blocks_the_order_and_keeps_the_cart(): void
    {
        $customer = $this->customer();
        $product = $this->product('Claw Hammer', 10, 350);
        $this->inCart($customer, $product, 3);
        $product->update(['stock_quantity' => 1]);                 // someone else bought it meanwhile

        // Opening checkout clamps the line to what is left and says so.
        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))
            ->assertSee('Only 1 of Claw Hammer left, so your quantity was changed to 1.');
        $this->assertSame(1, (int) $customer->cart->items()->first()->quantity);

        // Stock runs out after the page loaded: the order is refused and nothing is saved.
        $product->update(['stock_quantity' => 0]);
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(0, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, $customer->cart->items()->count());
    }

    public function test_a_product_marked_inactive_can_still_be_ordered(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->inCart($customer, $product, 1);
        $product->update(['is_active' => false]);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())
            ->assertRedirect(route('customer.orders.confirmation', Invoice::firstOrFail()));
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_a_failure_while_deducting_stock_rolls_everything_back(): void
    {
        $customer = $this->customer();
        $a = $this->product('Claw Hammer', 10, 350);
        $b = $this->product('Drill', 5, 120);
        $this->inCart($customer, $a, 2);
        $this->inCart($customer, $b, 1);

        // The second stock deduction fails, after the first one already went through.
        $this->app->bind(InventoryService::class, fn () => new class extends InventoryService
        {
            private int $calls = 0;

            public function deduct(int $productId, int $qty, string $referenceType, int $referenceId): Product
            {
                if (++$this->calls === 2) {
                    throw ValidationException::withMessages(['items' => 'Inventory update failed.']);
                }

                return parent::deduct($productId, $qty, $referenceType, $referenceId);
            }
        });

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())->assertSessionHasErrors('items');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame(10, (int) $a->fresh()->stock_quantity);
        $this->assertSame(5, (int) $b->fresh()->stock_quantity);
        $this->assertSame(2, $customer->cart->items()->count());
    }

    public function test_the_receipt_keeps_the_price_from_the_time_of_the_order(): void
    {
        $customer = $this->customer();
        $product = $this->product('Claw Hammer', 10, 350);
        $this->inCart($customer, $product, 1);
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details());

        $product->update(['unit_price' => 999]);

        $this->actingAs($customer, 'customer')->get(route('customer.orders.show', Invoice::firstOrFail()))
            ->assertOk()->assertSee('Claw Hammer')->assertSee('350.00')->assertDontSee('999.00');
    }

    public function test_only_the_owner_can_open_a_receipt(): void
    {
        $owner = $this->customer('owner@example.com');
        $other = $this->customer('other@example.com');
        $this->inCart($owner, $this->product(), 1);
        $this->actingAs($owner, 'customer')->post(route('customer.checkout.store'), $this->details());
        $invoice = Invoice::firstOrFail();

        $this->actingAs($owner, 'customer')->get(route('customer.orders.show', $invoice))->assertOk();
        $this->actingAs($other, 'customer')->get(route('customer.orders.show', $invoice))->assertNotFound();
    }

    public function test_guests_cannot_open_receipts_or_order_history(): void
    {
        $invoice = Invoice::create(['invoice_date' => today(), 'customer_id' => $this->customer()->customer_id, 'channel' => 'Online', 'status' => 'Pending', 'total_amount' => 10]);

        $this->get(route('customer.orders.show', $invoice))->assertRedirect(route('login', ['as' => 'customer']));
        $this->get(route('customer.orders.index'))->assertRedirect(route('login', ['as' => 'customer']));
    }

    public function test_order_history_lists_only_my_orders(): void
    {
        $me = $this->customer('me@example.com');
        $other = $this->customer('other@example.com');
        Invoice::create(['invoice_date' => today(), 'customer_id' => $me->customer_id, 'channel' => 'Online', 'status' => 'Pending', 'total_amount' => 111]);
        Invoice::create(['invoice_date' => today(), 'customer_id' => $other->customer_id, 'channel' => 'Online', 'status' => 'Pending', 'total_amount' => 222]);

        $this->actingAs($me, 'customer')->get(route('customer.orders.index'))
            ->assertOk()->assertSee('111.00')->assertDontSee('222.00');
    }

    // J2: the details removed from registration are asked for at checkout, and only when missing.
    public function test_new_customer_is_asked_for_all_details_marked_required(): void
    {
        $customer = $this->customer();                       // registered with email + password only
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))->assertOk()
            ->assertDontSee('All fields are required')
            ->assertSee('name="first_name"', false)->assertSee('name="last_name"', false)
            ->assertSee('name="contact_number"', false)->assertSee('name="address"', false);
    }

    public function test_customer_with_a_complete_profile_is_not_asked_again(): void
    {
        $customer = $this->customer();
        $customer->update(['first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '09170000000', 'address' => 'La Trinidad']);
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))->assertOk()
            ->assertSee('Name:')->assertSee('Maria Santos')->assertSee('La Trinidad')
            ->assertDontSee('name="first_name"', false)->assertDontSee('name="address"', false);

        // Only the payment method is sent; the saved details are used and left unchanged.
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), ['fulfillment' => 'Delivery', 'payment_method' => 'Cash'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame('La Trinidad', $customer->fresh()->address);
    }

    public function test_checkout_requires_correction_of_a_saved_short_contact_number(): void
    {
        $customer = $this->customer();
        $customer->update([
            'first_name' => 'Juan', 'last_name' => 'Cruz',
            'contact_number' => '123', 'address' => 'Baguio City',
        ]);
        $this->inCart($customer, $this->product(), 1);
        $this->actingAs($customer, 'customer');

        $this->get(route('customer.checkout.show'))->assertOk()->assertSee('name="contact_number"', false);
        $this->post(route('customer.checkout.store'), ['fulfillment' => 'Delivery', 'payment_method' => 'Cash'])
            ->assertSessionHasErrors('contact_number');
        $this->assertDatabaseCount('invoices', 0);

        $this->post(route('customer.checkout.store'), [
            'fulfillment' => 'Delivery', 'payment_method' => 'Cash', 'contact_number' => '0123456',
        ])->assertRedirect(route('customer.orders.confirmation', Invoice::firstOrFail()));
        $this->assertSame('0123456', $customer->fresh()->contact_number);
    }

    public function test_only_the_missing_details_are_required_and_saved(): void
    {
        $customer = $this->customer();
        $customer->update(['first_name' => 'Maria', 'last_name' => 'Santos']);     // contact + address still missing
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))
            ->assertDontSee('name="first_name"', false)->assertSee('name="contact_number"', false)->assertSee('name="address"', false);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), ['fulfillment' => 'Delivery', 'payment_method' => 'Cash'])
            ->assertSessionHasErrors(['contact_number', 'address'])->assertSessionDoesntHaveErrors('first_name');
        $this->assertDatabaseCount('invoices', 0);

        // A name sent for a field that is already saved is ignored.
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), [
            'fulfillment' => 'Delivery', 'first_name' => 'Hacked', 'contact_number' => '09171112222', 'address' => 'Baguio City', 'payment_method' => 'GCash',
        ])->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Maria', $customer->first_name);
        $this->assertSame('09171112222', $customer->contact_number);
        $this->assertSame('Baguio City', $customer->address);
    }

    public function test_guest_is_returned_to_checkout_after_logging_in(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product(), 1);

        $this->get(route('customer.checkout.show'))->assertRedirect(route('login', ['as' => 'customer']));
        $this->post(route('login'), ['login' => 'juan@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('customer.checkout.show'));
    }

    public function test_customer_never_sees_the_staff_low_stock_warning(): void
    {
        $customer = $this->customer();
        $product = Product::create([
            'category_id' => $this->cat->category_id, 'product_name' => 'Cordless Drill', 'unit_price' => 3200,
            'stock_quantity' => 3, 'reorder_level' => 3,                 // checkout drops it to the reorder level
        ]);
        $this->inCart($customer, $product, 1);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())
            ->assertSessionMissing('warning');
        $this->actingAs($customer, 'customer')->get(route('customer.orders.confirmation', Invoice::firstOrFail()))
            ->assertDontSee('Assign a supplier');
    }

    public function test_checkout_shows_money_format(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product('Claw Hammer', 10, 1234.5), 2);

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))
            ->assertSee('₱1,234.50')->assertSee('₱2,469.00');
    }

    // J3: confirmation, history and receipt.
    public function test_only_the_owner_can_open_the_confirmation(): void
    {
        $owner = $this->customer('owner@example.com');
        $other = $this->customer('other@example.com');
        $invoice = Invoice::create(['invoice_date' => today(), 'customer_id' => $owner->customer_id, 'channel' => 'Online', 'status' => 'Pending', 'total_amount' => 10]);

        $this->get(route('customer.orders.confirmation', $invoice))->assertRedirect(route('login', ['as' => 'customer']));   // guest first: actingAs() stays logged in
        $this->actingAs($other, 'customer')->get(route('customer.orders.confirmation', $invoice))->assertNotFound();
        $this->actingAs($owner, 'customer')->get(route('customer.orders.confirmation', $invoice))->assertOk();
    }

    public function test_order_history_shows_status_and_can_filter_by_it(): void
    {
        $me = $this->customer('me@example.com');
        Invoice::create(['invoice_date' => today(), 'customer_id' => $me->customer_id, 'channel' => 'Online', 'status' => 'Pending', 'total_amount' => 1500]);
        Invoice::create(['invoice_date' => today(), 'customer_id' => $me->customer_id, 'channel' => 'Online', 'status' => 'Completed', 'total_amount' => 2750.5]);

        $this->actingAs($me, 'customer')->get(route('customer.orders.index'))->assertOk()
            ->assertSee('₱1,500.00')->assertSee('₱2,750.50')
            ->assertSee('status-pending', false)->assertSee('status-completed', false);

        $this->actingAs($me, 'customer')->get(route('customer.orders.index', ['status' => 'Completed']))
            ->assertSee('₱2,750.50')->assertDontSee('₱1,500.00');
    }

    public function test_receipt_is_printable_and_uses_money_format(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product('Claw Hammer', 10, 1234.5), 2);
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details());

        $this->actingAs($customer, 'customer')->get(route('customer.orders.show', Invoice::firstOrFail()))
            ->assertOk()->assertSee('₱1,234.50')->assertSee('₱2,469.00')
            ->assertSee('window.print()', false)->assertSee('Pending');
    }

    public function test_pickup_is_the_default_and_needs_no_address(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product(), 1);
        $this->actingAs($customer, 'customer');

        // The choice is on the page with Pickup already selected; the address field only belongs to Delivery.
        $this->get(route('customer.checkout.show'))->assertOk()
            ->assertSee('How will you get your order?')
            ->assertSee('value="Pickup" checked', false)
            ->assertSee('data-show-for="Delivery"', false);

        $this->post(route('customer.checkout.store'), $this->details(['fulfillment' => 'Pickup', 'address' => '']))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('Pickup', $invoice->fulfillment);
        $this->assertNull($customer->fresh()->address);

        $this->get(route('customer.orders.confirmation', $invoice))->assertOk()
            ->assertSee('Pick up at store')->assertSee('prepare your order for pickup')->assertDontSee('Delivery Address');
        $this->get(route('customer.orders.show', $invoice))->assertOk()->assertSee('Pick up at store');
    }

    public function test_delivery_needs_an_address_and_staff_see_where_to_deliver(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details(['address' => '']))
            ->assertSessionHasErrors(['address' => 'Enter a delivery address, or choose Pick up at store.']);
        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details(['fulfillment' => 'Shipping']))
            ->assertSessionHasErrors('fulfillment');
        $this->assertDatabaseCount('invoices', 0);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details(['address' => 'Session Road, Baguio']))
            ->assertSessionHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $this->assertSame('Delivery', $invoice->fulfillment);

        $staff = User::create(['email' => 'supervisor-x@example.com', 'password' => 'secret123', 'role' => User::STORE_SUPERVISOR, 'status' => User::STATUS_ACTIVE]);
        $this->actingAs($staff, 'web')->get(route('online-orders.show', $invoice))->assertOk()
            ->assertSee('Delivery')->assertSee('Deliver to: Session Road, Baguio');
    }
}
