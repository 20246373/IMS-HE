<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'address' => 'Baguio City', 'payment_method' => 'Cash',
        ], $overrides);
    }

    public function test_guests_must_log_in_first(): void
    {
        $this->get(route('customer.checkout.show'))->assertRedirect(route('login'));
        $this->post(route('customer.checkout.store'), $this->details())->assertRedirect(route('login'));
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

    public function test_the_customer_lands_on_the_receipt(): void
    {
        $customer = $this->customer();
        $this->inCart($customer, $this->product(), 1);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())
            ->assertRedirect(route('customer.orders.show', Invoice::firstOrFail()));
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

        $this->actingAs($customer, 'customer')->get(route('customer.checkout.show'))->assertSee('Only 1 in stock');

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(1, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, $customer->cart->items()->count());
    }

    public function test_a_product_marked_inactive_can_still_be_ordered(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->inCart($customer, $product, 1);
        $product->update(['is_active' => false]);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout.store'), $this->details())
            ->assertRedirect(route('customer.orders.show', Invoice::firstOrFail()));
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
        $this->app->bind(InventoryService::class, fn () => new class extends InventoryService {
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

        $this->get(route('customer.orders.show', $invoice))->assertRedirect(route('login'));
        $this->get(route('customer.orders.index'))->assertRedirect(route('login'));
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
}
