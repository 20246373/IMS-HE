<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

// N4: automatic purchase orders created when a sale pushes stock to its reorder level.
class AutoPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['category_name' => 'Hardware']);
        $this->supplier = Supplier::create([
            'supplier_name' => 'Sample Supply',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
    }

    private function product(
        string $name = 'Claw Hammer',
        int $stock = 10,
        int $reorder = 5,
        bool $withSupplier = true,
    ): Product {
        return Product::create([
            'category_id' => $this->category->category_id,
            'supplier_id' => $withSupplier ? $this->supplier->supplier_id : null,
            'product_name' => $name,
            'brand' => 'Stanley',
            'unit_price' => 100,
            'stock_quantity' => $stock,
            'reorder_level' => $reorder,
            'is_active' => true,
        ]);
    }

    public function test_a_deduction_that_crosses_the_reorder_level_creates_one_auto_purchase_order(): void
    {
        $product = $this->product(stock: 10, reorder: 5);

        app(InventoryService::class)->deduct($product->product_id, 5, 'Invoice', 1);

        $purchaseOrder = PurchaseOrder::with('items')->sole();

        $this->assertTrue($purchaseOrder->is_auto);
        $this->assertSame('Pending', $purchaseOrder->status);
        $this->assertSame($this->supplier->supplier_id, (int) $purchaseOrder->supplier_id);
        $this->assertCount(1, $purchaseOrder->items);
        $this->assertSame($product->product_id, (int) $purchaseOrder->items[0]->product_id);
        $this->assertSame(10, (int) $purchaseOrder->items[0]->quantity);
        $this->assertSame(100.0, (float) $purchaseOrder->items[0]->unit_cost);
        $this->assertSame(1000.0, (float) $purchaseOrder->items[0]->subtotal);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Created',
            'table_name' => 'purchase_orders',
            'record_id' => $purchaseOrder->po_id,
        ]);

        // Further deductions while the same Pending PO exists must not create duplicates.
        app(InventoryService::class)->deduct($product->product_id, 1, 'Invoice', 2);

        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_items', 1);
    }

    public function test_no_auto_purchase_order_is_created_when_a_pending_po_already_contains_the_product(): void
    {
        $product = $this->product(stock: 10, reorder: 5);
        $manual = PurchaseOrder::create([
            'supplier_id' => $this->supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
            'is_auto' => false,
        ]);
        PurchaseOrderItem::create([
            'po_id' => $manual->po_id,
            'product_id' => $product->product_id,
            'quantity' => 3,
            'quantity_received' => 0,
            'unit_cost' => 90,
            'subtotal' => 270,
        ]);

        app(InventoryService::class)->deduct($product->product_id, 5, 'Invoice', 1);

        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_items', 1);
        $this->assertSame(3, (int) PurchaseOrderItem::sole()->quantity);
    }

    public function test_a_low_stock_product_without_a_supplier_gets_a_warning_instead_of_a_po(): void
    {
        $product = $this->product(stock: 10, reorder: 5, withSupplier: false);

        app(InventoryService::class)->deduct($product->product_id, 5, 'Invoice', 1);

        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertStringContainsString('no default supplier', (string) session('warning'));

        $this->get(route('home'))->assertOk()->assertSee('no default supplier');
    }

    public function test_the_auto_po_uses_the_last_po_items_unit_cost(): void
    {
        $product = $this->product(stock: 10, reorder: 5);
        $previous = PurchaseOrder::create([
            'supplier_id' => $this->supplier->supplier_id,
            'order_date' => today()->subMonth(),
            'status' => 'Delivered',
            'is_auto' => false,
        ]);
        PurchaseOrderItem::create([
            'po_id' => $previous->po_id,
            'product_id' => $product->product_id,
            'quantity' => 5,
            'quantity_received' => 5,
            'unit_cost' => 87.5,
            'subtotal' => 437.5,
        ]);

        app(InventoryService::class)->deduct($product->product_id, 5, 'Invoice', 1);

        $auto = PurchaseOrder::where('is_auto', true)->sole();
        $this->assertSame(87.5, (float) $auto->items()->sole()->unit_cost);
    }

    public function test_a_customer_checkout_creates_the_auto_po_without_using_the_customer_as_the_audit_user(): void
    {
        $customer = Customer::create([
            'email' => 'checkout@example.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);
        $product = $this->product(stock: 6, reorder: 5);
        app(CartService::class)->add($customer, $product, 1);

        $this->actingAs($customer, 'customer');

        app(CheckoutService::class)->placeOrder($customer, [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'contact_number' => '09171234567',
            'address' => 'Baguio City',
        ], 'Cash');

        $purchaseOrder = PurchaseOrder::where('is_auto', true)->sole();

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('purchase_order_items', [
            'po_id' => $purchaseOrder->po_id,
            'product_id' => $product->product_id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Created',
            'table_name' => 'purchase_orders',
            'record_id' => $purchaseOrder->po_id,
            'user_id' => null,
        ]);
    }

    public function test_the_auto_po_and_stock_change_roll_back_when_the_sale_fails(): void
    {
        $customer = Customer::create([
            'email' => 'buyer@example.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);
        $first = $this->product('Claw Hammer', stock: 10, reorder: 5);
        $second = $this->product('Drill', stock: 5, reorder: 0);

        app(CartService::class)->add($customer, $first, 5);
        app(CartService::class)->add($customer, $second, 1);

        // The second deduction fails after the first has already created its auto PO.
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

        try {
            app(CheckoutService::class)->placeOrder($customer, [
                'first_name' => 'Juan',
                'last_name' => 'Cruz',
                'contact_number' => '09171234567',
                'address' => 'Baguio City',
            ], 'Cash');

            $this->fail('The failed deduction should abort the checkout.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('purchase_order_items', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame(10, (int) $first->fresh()->stock_quantity);
        $this->assertSame(5, (int) $second->fresh()->stock_quantity);
    }

    public function test_the_purchase_order_list_marks_auto_generated_orders(): void
    {
        $clerk = User::create([
            'username' => 'auto-po-clerk',
            'password' => 'secret123',
            'role' => User::INVENTORY_CLERK,
            'status' => User::STATUS_ACTIVE,
        ]);
        $auto = PurchaseOrder::create([
            'supplier_id' => $this->supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
            'is_auto' => true,
        ]);
        $manual = PurchaseOrder::create([
            'supplier_id' => $this->supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
            'is_auto' => false,
        ]);

        $this->actingAs($clerk)->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee('#'.$auto->po_id)
            ->assertSee('#'.$manual->po_id)
            ->assertSee('<span class="badge amber">Auto</span>', false);
    }
}
