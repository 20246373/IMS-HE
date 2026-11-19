<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// UC12 Add / UC13 Edit / UC14 Delete for products, and the supplier role rules.
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = Category::create(['category_name' => 'Hand Tools']);
    }

    private function staff(string $role, ?string $username = null): User
    {
        return User::create([
            'email' => ($username ?? strtolower(str_replace(' ', '', $role))).'@example.com',
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Claw Hammer',
            'brand' => 'Stanley',
            'category_id' => $this->cat->category_id,
            'description' => '16oz claw hammer',
            'unit_price' => '350.00',
            'stock_quantity' => 20,
            'reorder_level' => 5,
        ], $overrides);
    }

    private function product(string $name = 'Claw Hammer', int $stock = 10): Product
    {
        return Product::create([
            'category_id' => $this->cat->category_id, 'product_name' => $name, 'brand' => 'Stanley', 'unit_price' => 350,
            'stock_quantity' => $stock, 'reorder_level' => 0, 'is_active' => true,
        ]);
    }

    public function test_clerk_can_add_a_product_with_brand_and_opening_stock_logged(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload())
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_name', 'Claw Hammer')->firstOrFail();
        $this->assertSame('Stanley', $product->brand);
        $this->assertSame(20, (int) $product->stock_quantity);
        $this->assertTrue((bool) $product->is_active);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id, 'transaction_type' => 'IN', 'quantity' => 20, 'reference_type' => 'Adjustment',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created', 'table_name' => 'products', 'record_id' => $product->product_id]);
    }

    public function test_clerk_can_add_a_product_with_an_image(): void
    {
        Storage::fake('public');
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload([
            'image' => UploadedFile::fake()->image('claw-hammer.jpg', 100, 100),
        ]))->assertRedirect(route('products.index'));

        $product = Product::where('product_name', 'Claw Hammer')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_image_must_be_a_supported_image(): void
    {
        Storage::fake('public');
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload([
            'image' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors(['image']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_employee_cannot_add_a_product_with_an_image(): void
    {
        Storage::fake('public');
        $employee = $this->staff(User::EMPLOYEE);

        $this->actingAs($employee)->post(route('products.store'), $this->payload([
            'image' => UploadedFile::fake()->image('claw-hammer.jpg', 100, 100),
        ]))->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_brand_is_required_for_product_creation(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload(['brand' => '']))
            ->assertSessionHasErrors(['brand']);
    }

    public function test_admin_search_matches_product_name_or_brand(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $this->product('Claw Hammer');
        $target = Product::create([
            'category_id' => $this->cat->category_id,
            'product_name' => 'Screwdriver Set',
            'brand' => 'Bosch',
            'unit_price' => 450,
            'stock_quantity' => 12,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($clerk)->get(route('products.index', ['q' => 'Bosch']))
            ->assertOk()
            ->assertSee($target->product_name)
            ->assertSee('Bosch');
    }

    public function test_product_forms_and_list_show_the_brand(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $this->product('Claw Hammer');

        $this->actingAs($clerk)->get(route('products.create'))
            ->assertOk()
            ->assertSee('name="brand"', false)
            ->assertDontSee('All fields are required unless marked');

        $this->actingAs($clerk)->get(route('products.edit', Product::firstOrFail()))
            ->assertOk()
            ->assertSee('name="brand"', false);

        $this->actingAs($clerk)->get(route('products.index'))
            ->assertOk()
            ->assertSee('<th>Brand</th>', false)
            ->assertSee('Stanley');
    }

    public function test_duplicate_name_and_invalid_data_are_rejected(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $this->product('Claw Hammer');

        $this->actingAs($clerk)->post(route('products.store'), $this->payload())
            ->assertSessionHasErrors(['product_name' => 'A record with this name already exists.']);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload(['product_name' => 'Saw', 'category_id' => '', 'unit_price' => '-5', 'stock_quantity' => 'abc']))
            ->assertSessionHasErrors(['category_id', 'unit_price', 'stock_quantity']);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_edit_updates_only_editable_fields_and_ignores_stock_and_product_id(): void
    {
        $supervisor = $this->staff(User::STORE_SUPERVISOR);
        $supplier = Supplier::create(['supplier_name' => 'Sample Supply', 'contact_number' => '09170000000', 'address' => 'Baguio City']);
        $product = $this->product('Claw Hammer', stock: 10);
        $productId = $product->product_id;
        $other = $this->product('Other Product', stock: 3);

        $this->actingAs($supervisor)->put(route('products.update', $product), $this->payload([
            'product_name' => 'Claw Hammer Pro',
            'brand' => 'Milwaukee',
            'unit_price' => '400.00',
            'reorder_level' => 8,
            'supplier_id' => $supplier->supplier_id,
            // These two are not editable and must be ignored even when tampered with.
            'stock_quantity' => 7,
            'product_id' => $other->product_id,
        ]))->assertRedirect(route('products.index'))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Claw Hammer Pro', $product->product_name);
        $this->assertSame('Milwaukee', $product->brand);
        $this->assertSame('400.00', $product->unit_price);
        $this->assertSame(8, (int) $product->reorder_level);
        $this->assertSame($supplier->supplier_id, (int) $product->supplier_id);

        $this->assertSame(10, (int) $product->stock_quantity);
        $this->assertSame($productId, (int) $product->product_id);
        $this->assertSame($other->product_id, (int) $other->fresh()->product_id);
        $this->assertSame(3, (int) $other->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_product_edit_shows_product_id_and_stock_as_read_only(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $product = $this->product('Claw Hammer', stock: 10);

        $this->actingAs($clerk)->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Product ID')
            ->assertSee('value="'.$product->product_id.'"', false)
            ->assertSee('value="10"', false)
            ->assertDontSee('name="stock_quantity"', false);
    }

    public function test_product_without_history_can_be_deleted(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $this->actingAs($clerk)->post(route('products.store'), $this->payload());
        $product = Product::firstOrFail();

        $this->actingAs($clerk)->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_product_linked_to_a_purchase_order_cannot_be_deleted(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $product = $this->product();
        $supplier = Supplier::create(['supplier_name' => 'Sample Supply', 'contact_number' => '09170000000', 'address' => 'Baguio City']);
        $po = PurchaseOrder::create(['supplier_id' => $supplier->supplier_id, 'order_date' => today(), 'status' => 'Pending']);
        PurchaseOrderItem::create(['po_id' => $po->po_id, 'product_id' => $product->product_id, 'quantity' => 1, 'unit_cost' => 10, 'subtotal' => 10]);

        $this->actingAs($clerk)->delete(route('products.destroy', $product))
            ->assertSessionHasErrors(['product' => 'Cannot delete — this product is used in existing purchase order items.']);

        $this->assertDatabaseHas('products', ['product_id' => $product->product_id]);
    }

    public function test_product_linked_to_an_invoice_cannot_be_deleted(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $product = $this->product();
        $invoice = Invoice::create(['invoice_date' => today(), 'channel' => 'Walk-in', 'status' => 'Pending', 'total_amount' => 350]);
        InvoiceItem::create([
            'invoice_id' => $invoice->invoice_id, 'product_id' => $product->product_id,
            'quantity' => 1, 'price' => 350, 'subtotal' => 350,
        ]);

        $this->actingAs($clerk)->delete(route('products.destroy', $product))
            ->assertSessionHasErrors(['product' => 'Cannot delete — this product is used in existing invoice items.']);

        $this->assertDatabaseHas('products', ['product_id' => $product->product_id]);
    }

    public function test_product_in_a_customer_cart_cannot_be_deleted(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $product = $this->product();
        $customer = Customer::create(['email' => 'cart@example.com', 'password' => 'secret123']);
        $cart = Cart::create(['customer_id' => $customer->customer_id]);
        CartItem::create(['cart_id' => $cart->cart_id, 'product_id' => $product->product_id, 'quantity' => 2]);

        $this->actingAs($clerk)->delete(route('products.destroy', $product))
            ->assertSessionHasErrors(['product' => 'Cannot delete — this product is in a customer cart.']);

        $this->assertDatabaseHas('products', ['product_id' => $product->product_id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->product_id]);
    }

    public function test_the_product_list_has_no_hide_or_show_controls(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $this->product();

        $this->actingAs($clerk)->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('Hide')
            ->assertDontSee('Hidden')
            ->assertDontSee('<th>Status</th>', false);

        $this->assertFalse(Route::has('products.toggle'));
    }

    public function test_only_clerk_and_supervisor_can_manage_products(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login', ['as' => 'employee']));

        foreach ([User::EMPLOYEE, User::TREASURER] as $role) {
            $this->actingAs($this->staff($role))->get(route('products.index'))->assertForbidden();
        }

        $this->actingAs($this->staff(User::INVENTORY_CLERK))->get(route('products.index'))->assertOk();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get(route('products.index'))->assertOk();
        $this->actingAs($this->staff(User::PRESIDENT))->get(route('products.index'))->assertOk();
    }

    public function test_supplier_roles_follow_the_use_cases(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Sample Supply', 'contact_number' => '09170000000', 'address' => 'Baguio City']);

        $this->actingAs($this->staff(User::INVENTORY_CLERK))->get(route('suppliers.index'))->assertOk();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get(route('suppliers.index'))->assertOk();
        $this->actingAs($this->staff(User::PRESIDENT))->get(route('suppliers.index'))->assertOk();

        // delete is President only
        $this->actingAs($this->staff(User::STORE_SUPERVISOR, 'supervisor-delete'))->delete(route('suppliers.destroy', $supplier))->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT, 'president-delete'))->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_supplier_with_purchase_orders_cannot_be_deleted(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Sample Supply', 'contact_number' => '09170000000', 'address' => 'Baguio City']);
        PurchaseOrder::create(['supplier_id' => $supplier->supplier_id, 'order_date' => today(), 'status' => 'Pending']);

        $this->actingAs($this->staff(User::PRESIDENT))->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHasErrors(['supplier' => 'Cannot delete — this supplier is linked to existing purchase orders.']);
    }
}
