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
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'username' => $username, 'email' => "$username@example.com",
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
            ->assertRedirect(route('customer.login'));
        $this->get('/cart')->assertRedirect(route('customer.login'));
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

    public function test_quantity_cannot_exceed_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(stock: 4);

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 3]);
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $product), ['quantity' => 2])
            ->assertSessionHasErrors('quantity');            // 3 + 2 > 4

        $this->assertSame(3, (int) CartItem::first()->quantity);
    }

    public function test_out_of_stock_and_inactive_products_cannot_be_added(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $this->product('Empty', stock: 0)), ['quantity' => 1])
            ->assertSessionHasErrors('quantity');
        $this->actingAs($customer, 'customer')->post(route('customer.cart.add', $this->product('Hidden', active: false)), ['quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
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
}
