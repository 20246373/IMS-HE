<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = Category::create(['category_name' => 'Hand Tools']);
    }

    private function customer(string $username = 'juan123'): Customer
    {
        return Customer::create([
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'email' => "$username@example.com",
            'contact_number' => '09171234567', 'address' => 'Baguio City', 'password' => 'secret123',
        ]);
    }

    private function product(string $name = 'Claw Hammer', int $stock = 10, float $price = 350, bool $active = true): Product
    {
        return Product::create([
            'category_id' => $this->cat->category_id, 'product_name' => $name, 'unit_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => 0, 'is_active' => $active,
        ]);
    }

    public function test_guest_adding_to_cart_is_redirected_to_customer_login(): void
    {
        $product = $this->product();

        $this->from(route('catalog.show', $product))->post(route('customer.cart.add', $product), ['quantity' => 1])
            ->assertRedirect(route('login', ['as' => 'customer']));
        $this->get('/cart')->assertRedirect(route('login', ['as' => 'customer']));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_adding_same_product_twice_keeps_one_cart_and_one_line(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 2])
            ->assertRedirect(route('customer.cart.index'));
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 3]);

        $this->assertSame(1, Cart::count());
        $this->assertSame(1, CartItem::count());
        $this->assertSame(5, (int) CartItem::first()->quantity);
    }

    public function test_background_add_returns_the_new_cart_count(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'customer')->postJson(route('customer.cart.add', $product), ['quantity' => 2])
            ->assertOk()
            ->assertJson(['count' => 2, 'message' => 'Claw Hammer added to your cart.']);
        $this->actingAs($customer, 'customer')->postJson(route('customer.cart.add', $this->product('Nails')), ['quantity' => 3])
            ->assertJson(['count' => 5]);
    }

    public function test_background_add_from_a_guest_is_unauthorized_and_beyond_stock_is_refused(): void
    {
        $product = $this->product(stock: 2);

        $this->postJson(route('customer.cart.add', $product), ['quantity' => 1])->assertUnauthorized();
        $this->actingAs($this->customer(), 'customer')->postJson(route('customer.cart.add', $product), ['quantity' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(stock: 4);

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 3]);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 2])
            ->assertSessionHasErrors('quantity');            // 3 + 2 > 4

        $this->assertSame(3, (int) CartItem::first()->quantity);
    }

    public function test_invalid_quantities_do_not_add_or_update_cart_items(): void
    {
        $this->actingAs($this->customer(), 'customer');
        $product = $this->product();
        $add = route('customer.cart.add', $product);
        $invalid = [[], ['quantity' => 0], ['quantity' => -1], ['quantity' => '1.5'], ['quantity' => 10000]];

        foreach ($invalid as $input) {
            $this->postJson($add, $input)->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->assertDatabaseCount('cart_items', 0);

        $this->postJson($add, ['quantity' => 1])->assertOk();
        $item = CartItem::firstOrFail();

        foreach ($invalid as $input) {
            $this->patchJson(route('customer.cart.update', $item), $input)
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->assertSame(1, (int) $item->fresh()->quantity);
    }

    public function test_out_of_stock_products_cannot_be_added_but_inactive_ones_can(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $this->product('Empty', stock: 0)), ['quantity' => 1])
            ->assertSessionHasErrors('quantity');
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $this->product('Inactive', active: false)), ['quantity' => 1])
            ->assertRedirect(route('customer.cart.index'));

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_update_and_remove_items_and_totals_are_recalculated(): void
    {
        $customer = $this->customer();
        $hammer = $this->product('Claw Hammer', price: 350);
        $nails = $this->product('Common Nails', price: 85);

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $hammer), ['quantity' => 1]);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $nails), ['quantity' => 2]);
        $this->actingAs($customer, 'customer')->get('/cart')->assertOk()->assertSee('520.00');   // 350 + 2 x 85

        $line = CartItem::where('product_id', $hammer->product_id)->first();
        $this->actingAs($customer, 'customer')->patch(route('customer.cart.update', $line), ['quantity' => 3]);
        $this->actingAs($customer, 'customer')->get('/cart')->assertSee('1,220.00');             // 3 x 350 + 170

        $this->actingAs($customer, 'customer')->patch(route('customer.cart.update', $line), ['quantity' => 11])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($customer, 'customer')->delete(route('customer.cart.remove', $line));
        $this->assertDatabaseMissing('cart_items', ['cart_item_id' => $line->cart_item_id]);
        $this->actingAs($customer, 'customer')->get('/cart')->assertSee('170.00')->assertDontSee('Claw Hammer');
    }

    public function test_background_update_returns_recalculated_totals(): void
    {
        $customer = $this->customer();
        $hammer = $this->product('Claw Hammer', stock: 5, price: 350);
        $nails = $this->product('Common Nails', price: 85);

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $hammer), ['quantity' => 1]);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $nails), ['quantity' => 2]);
        $line = CartItem::where('product_id', $hammer->product_id)->first();

        $this->actingAs($customer, 'customer')->patchJson(route('customer.cart.update', $line), ['quantity' => 3])
            ->assertOk()
            ->assertJson(['quantity' => 3, 'subtotal' => 1050, 'total' => 1220, 'count' => 5]);   // 3 x 350 + 2 x 85

        $this->actingAs($customer, 'customer')->patchJson(route('customer.cart.update', $line), ['quantity' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');                                          // only 5 in stock
        $this->assertSame(3, (int) $line->fresh()->quantity);
    }

    public function test_customer_cannot_touch_another_customers_cart_line(): void
    {
        $owner = $this->customer('owner1');
        $other = $this->customer('other1');
        $product = $this->product();

        $this->actingAs($owner, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 1]);
        $line = CartItem::first();

        $this->actingAs($other, 'customer')->patch(route('customer.cart.update', $line), ['quantity' => 2])->assertNotFound();
        $this->actingAs($other, 'customer')->delete(route('customer.cart.remove', $line))->assertNotFound();
        $this->assertSame(1, (int) $line->fresh()->quantity);
    }

    public function test_quantity_above_current_stock_is_clamped_with_a_warning(): void
    {
        $customer = $this->customer();
        $product = $this->product(stock: 10);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 6]);

        $product->update(['stock_quantity' => 4]);          // someone else bought some meanwhile

        $this->actingAs($customer, 'customer')->get('/cart')->assertOk()
            ->assertSee('Your cart was updated')
            ->assertSee('Only 4 of Claw Hammer left, so your quantity was changed to 4.')
            ->assertSee('₱1,400.00');                       // 4 x 350, recalculated
        $this->assertSame(4, (int) CartItem::first()->quantity);
    }

    public function test_sold_out_product_is_removed_from_the_cart_with_a_warning(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 2]);

        $product->update(['stock_quantity' => 0]);

        $this->actingAs($customer, 'customer')->get('/cart')->assertOk()
            ->assertSee('Claw Hammer is out of stock and was removed from your cart.')
            ->assertSee('Your cart is empty.');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_price_change_since_adding_is_flagged_once(): void
    {
        $customer = $this->customer();
        $product = $this->product(price: 350);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 1]);
        $this->actingAs($customer, 'customer')->get('/cart')->assertDontSee('changed from');

        $product->update(['unit_price' => 399.5]);

        $this->actingAs($customer, 'customer')->get('/cart')
            ->assertSee('The price of Claw Hammer changed from ₱350.00 to ₱399.50.')
            ->assertSee('₱399.50');
        $this->actingAs($customer, 'customer')->get('/cart')->assertDontSee('changed from');   // only reported once
    }

    public function test_cart_shows_money_format(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $this->product(price: 1234.5)), ['quantity' => 2]);

        $this->actingAs($customer, 'customer')->get('/cart')->assertSee('₱1,234.50')->assertSee('₱2,469.00');
    }
}
