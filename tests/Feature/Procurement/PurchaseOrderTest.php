<?php

namespace Tests\Feature\Procurement;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::INVENTORY_CLERK]);
        $this->actingAs($this->user);
    }

    public function test_new_po_saved_with_status_pending(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 50.00],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => 'Pending',
        ]);
    }

    public function test_submitted_status_or_total_ignored(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'status' => 'Delivered',
            'total' => 999999.99,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 50.00],
            ],
        ]);

        $response->assertRedirect();
        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $this->assertEquals('Pending', $po->status);
        $this->assertEquals(500.00, $po->total());
    }

    public function test_subtotal_and_total_computed_server_side(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 50.00],
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 20.00],
            ],
        ]);

        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $items = $po->items;
        $this->assertEquals(500.00, $items[0]->subtotal);
        $this->assertEquals(100.00, $items[1]->subtotal);
        $this->assertEquals(600.00, $po->total());
    }

    public function test_po_with_no_lines_rejected(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [],
        ]);

        $response->assertSessionHasErrors(['lines']);
        $this->assertDatabaseMissing('purchase_orders', ['supplier_id' => $supplier->id]);
    }

    public function test_non_positive_quantity_rejected(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 0, 'unit_cost' => 50.00],
            ],
        ]);

        $response->assertSessionHasErrors(['lines.0.quantity']);
    }

    public function test_negative_unit_cost_rejected(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => -10.00],
            ],
        ]);

        $response->assertSessionHasErrors(['lines.0.unit_cost']);
    }

    public function test_non_existent_product_rejected(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'lines' => [
                ['product_id' => 99999, 'quantity' => 10, 'unit_cost' => 50.00],
            ],
        ]);

        $response->assertSessionHasErrors(['lines.0.product_id']);
    }
}
