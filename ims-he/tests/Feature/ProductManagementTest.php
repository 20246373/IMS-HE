<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function staff(string $role, string $username = null): User
    {
        return User::create([
            'username' => $username ?? strtolower(str_replace(' ', '', $role)),
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Claw Hammer',
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
            'category_id' => $this->cat->category_id, 'product_name' => $name, 'unit_price' => 350,
            'stock_quantity' => $stock, 'reorder_level' => 0, 'is_active' => true,
        ]);
    }

    public function test_clerk_can_add_a_product_with_opening_stock_logged(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->post(route('products.store'), $this->payload())
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_name', 'Claw Hammer')->firstOrFail();
        $this->assertSame(20, (int) $product->stock_quantity);
        $this->assertTrue((bool) $product->is_active);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id, 'transaction_type' => 'IN', 'quantity' => 20, 'reference_type' => 'Adjustment',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created', 'table_name' => 'products', 'record_id' => $product->product_id]);
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

    public function test_edit_updates_fields_and_logs_a_stock_adjustment(): void
    {
        $supervisor = $this->staff(User::STORE_SUPERVISOR);
        $product = $this->product('Claw Hammer', stock: 10);

        $this->actingAs($supervisor)->put(route('products.update', $product), $this->payload([
            'product_name' => 'Claw Hammer Pro', 'unit_price' => '400.00', 'stock_quantity' => 7,
        ]))->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame('Claw Hammer Pro', $product->product_name);
        $this->assertSame(7, (int) $product->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id, 'transaction_type' => 'OUT', 'quantity' => 3, 'reference_type' => 'Adjustment',
        ]);
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
            ->assertSessionHasErrors(['product' => 'Cannot delete — this product is linked to existing transactions.']);

        $this->assertDatabaseHas('products', ['product_id' => $product->product_id]);
    }

    public function test_toggle_hides_and_shows_a_product(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK);
        $product = $this->product();

        $this->actingAs($clerk)->patch(route('products.toggle', $product));
        $this->assertFalse((bool) $product->fresh()->is_active);

        $this->actingAs($clerk)->patch(route('products.toggle', $product));
        $this->assertTrue((bool) $product->fresh()->is_active);
    }

    public function test_only_clerk_and_supervisor_can_manage_products(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));

        foreach ([User::EMPLOYEE, User::TREASURER, User::PRESIDENT] as $role) {
            $this->actingAs($this->staff($role))->get(route('products.index'))->assertForbidden();
        }

        $this->actingAs($this->staff(User::INVENTORY_CLERK))->get(route('products.index'))->assertOk();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get(route('products.index'))->assertOk();
    }

    public function test_supplier_roles_follow_the_use_cases(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Sample Supply', 'contact_number' => '09170000000', 'address' => 'Baguio City']);

        $this->actingAs($this->staff(User::INVENTORY_CLERK))->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get(route('suppliers.index'))->assertOk();

        // delete is President only
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->delete(route('suppliers.destroy', $supplier))->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT))->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
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
