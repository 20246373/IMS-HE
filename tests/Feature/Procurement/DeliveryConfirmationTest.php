<?php

namespace Tests\Feature\Procurement;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::INVENTORY_CLERK]);
        $this->actingAs($this->user);
    }

    public function test_partial_receipt_stock_increases_po_stays_pending(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();
        $product = $item->product;
        $product->stock_quantity = 0;
        $product->save();

        $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 6],
        ]);

        $item->refresh();
        $product->refresh();
        $po->refresh();

        $this->assertEquals(6, $item->quantity_received);
        $this->assertEquals(6, $product->stock_quantity);
        $this->assertEquals('Pending', $po->status);
    }

    public function test_full_receipt_stock_increases_po_becomes_delivered(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();
        $product = $item->product;
        $product->stock_quantity = 0;
        $product->save();

        $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 10],
        ]);

        $item->refresh();
        $product->refresh();
        $po->refresh();

        $this->assertEquals(10, $item->quantity_received);
        $this->assertEquals(10, $product->stock_quantity);
        $this->assertEquals('Delivered', $po->status);
        $this->assertNotNull($po->received_at);
    }

    public function test_over_receipt_rejected_nothing_changes(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();
        $product = $item->product;
        $product->stock_quantity = 0;
        $product->save();

        $response = $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 15],
        ]);

        $response->assertSessionHasErrors(['received']);
        $item->refresh();
        $product->refresh();

        $this->assertEquals(0, $item->quantity_received);
        $this->assertEquals(0, $product->stock_quantity);
    }

    public function test_cumulative_over_receipt_rejected(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();

        $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 6],
        ]);

        $item->refresh();
        $this->assertEquals(6, $item->quantity_received);

        $response = $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 6],
        ]);

        $response->assertSessionHasErrors(['received']);
        $item->refresh();

        $this->assertEquals(6, $item->quantity_received);
    }

    public function test_rollback_on_inventory_service_error(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(2), 'items')
            ->create();
        $items = $po->items;
        $product1 = $items[0]->product;
        $product2 = $items[1]->product;

        $product1->stock_quantity = 0;
        $product1->save();
        $product2->stock_quantity = 0;
        $product2->save();

        $this->mock(InventoryService::class, function ($mock) use ($product2, $po) {
            $mock->shouldReceive('add')
                ->once()
                ->andThrow(ValidationException::withMessages(['items' => 'Test error']));
        });

        $this->post(route('deliveries.store', $po), [
            'received' => [
                $items[0]->id => 10,
                $items[1]->id => 5,
            ],
        ]);

        $items[0]->refresh();
        $items[1]->refresh();
        $product1->refresh();
        $product2->refresh();

        $this->assertEquals(0, $items[0]->quantity_received);
        $this->assertEquals(0, $items[1]->quantity_received);
        $this->assertEquals(0, $product1->stock_quantity);
        $this->assertEquals(0, $product2->stock_quantity);
    }

    public function test_no_double_add_already_delivered_po_rejected(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 10]), 'items')
            ->create(['status' => 'Delivered']);
        $item = $po->items->first();
        $product = $item->product;
        $product->stock_quantity = 10;
        $product->save();

        $response = $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 5],
        ]);

        $response->assertSessionHasErrors(['status']);
        $product->refresh();

        $this->assertEquals(10, $product->stock_quantity);
    }

    public function test_foreign_item_id_rejected(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();

        $otherPo = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 5, 'quantity_received' => 0]), 'items')
            ->create();
        $otherItem = $otherPo->items->first();

        $response = $this->post(route('deliveries.store', $po), [
            'received' => [$otherItem->id => 5],
        ]);

        $response->assertSessionHasErrors(['received']);
    }

    public function test_inventory_transaction_written_with_po_reference(): void
    {
        $po = PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->state(['quantity' => 10, 'quantity_received' => 0]), 'items')
            ->create();
        $item = $po->items->first();

        $this->post(route('deliveries.store', $po), [
            'received' => [$item->id => 10],
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $item->product_id,
            'type' => 'IN',
            'quantity' => 10,
            'reference_type' => 'PO',
            'reference_id' => (string) $po->id,
        ]);
    }
}
