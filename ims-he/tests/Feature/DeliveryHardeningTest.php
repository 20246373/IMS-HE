<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// N5: partial deliveries, over-receiving protection, and stock updates for purchase orders.
class DeliveryHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $clerk;

    private Category $category;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clerk = User::create([
            'username' => 'delivery-clerk',
            'password' => 'secret123',
            'role' => User::INVENTORY_CLERK,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->category = Category::create(['category_name' => 'Hardware']);
        $this->supplier = Supplier::create([
            'supplier_name' => 'Sample Supply',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
    }

    private function product(string $name, int $stock = 10, int $reorder = 5): Product
    {
        return Product::create([
            'category_id' => $this->category->category_id,
            'supplier_id' => $this->supplier->supplier_id,
            'product_name' => $name,
            'brand' => 'Stanley',
            'unit_price' => 100,
            'stock_quantity' => $stock,
            'reorder_level' => $reorder,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, array{0: Product, 1: int, 2?: int}>  $lines
     */
    private function purchaseOrder(array $lines): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $this->supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
            'is_auto' => false,
        ]);

        foreach ($lines as $line) {
            [$product, $quantity] = $line;
            $received = $line[2] ?? 0;

            PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->po_id,
                'product_id' => $product->product_id,
                'quantity' => $quantity,
                'quantity_received' => $received,
                'unit_cost' => $product->unit_price,
                'subtotal' => round($quantity * (float) $product->unit_price, 2),
            ]);
        }

        return $purchaseOrder->load('items');
    }

    /**
     * @param  array<int, int>  $quantities  po_item_id => quantity received
     */
    private function deliveryPayload(PurchaseOrder $purchaseOrder, array $quantities): array
    {
        $items = [];

        foreach ($purchaseOrder->items as $item) {
            if (array_key_exists($item->po_item_id, $quantities)) {
                $items[] = [
                    'po_item_id' => $item->po_item_id,
                    'quantity_received' => $quantities[$item->po_item_id],
                ];
            }
        }

        return [
            'purchase_order_id' => $purchaseOrder->po_id,
            'items' => $items,
        ];
    }

    public function test_over_receiving_is_rejected_without_changing_stock(): void
    {
        $product = $this->product('Claw Hammer', stock: 10);
        $purchaseOrder = $this->purchaseOrder([[$product, 10]]);
        $item = $purchaseOrder->items->first();

        $response = $this->from(route('deliveries.create', ['purchase_order_id' => $purchaseOrder->po_id]))
            ->actingAs($this->clerk)
            ->post(route('deliveries.store'), $this->deliveryPayload($purchaseOrder, [
                $item->po_item_id => 11,
            ]));

        $response->assertSessionHasErrors('items.0.quantity_received');

        $this->assertSame(0, (int) $item->fresh()->quantity_received);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->get(route('deliveries.create', ['purchase_order_id' => $purchaseOrder->po_id]))
            ->assertOk()
            ->assertSee('alert-error', false)
            ->assertSee('Cannot receive 11');
    }

    public function test_partial_delivery_keeps_the_purchase_order_pending_and_increases_stock(): void
    {
        $first = $this->product('Claw Hammer', stock: 10);
        $second = $this->product('Drill', stock: 5);
        $purchaseOrder = $this->purchaseOrder([[$first, 10], [$second, 3]]);
        $firstItem = $purchaseOrder->items->firstWhere('product_id', $first->product_id);
        $secondItem = $purchaseOrder->items->firstWhere('product_id', $second->product_id);

        $this->actingAs($this->clerk)
            ->post(route('deliveries.store'), $this->deliveryPayload($purchaseOrder, [
                $firstItem->po_item_id => 4,
            ]))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->assertSame('Pending', $purchaseOrder->fresh()->status);
        $this->assertSame(4, (int) $firstItem->fresh()->quantity_received);
        $this->assertSame(0, (int) $secondItem->fresh()->quantity_received);
        $this->assertSame(14, (int) $first->fresh()->stock_quantity);
        $this->assertSame(5, (int) $second->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $first->product_id,
            'transaction_type' => 'IN',
            'quantity' => 4,
            'reference_type' => 'PO',
            'reference_id' => $purchaseOrder->po_id,
        ]);
    }

    public function test_completing_the_remaining_lines_marks_the_purchase_order_delivered(): void
    {
        $first = $this->product('Claw Hammer', stock: 10);
        $second = $this->product('Drill', stock: 5);
        $purchaseOrder = $this->purchaseOrder([[$first, 10], [$second, 3]]);
        $firstItem = $purchaseOrder->items->firstWhere('product_id', $first->product_id);
        $secondItem = $purchaseOrder->items->firstWhere('product_id', $second->product_id);

        $this->actingAs($this->clerk)->post(route('deliveries.store'), $this->deliveryPayload($purchaseOrder, [
            $firstItem->po_item_id => 4,
            $secondItem->po_item_id => 1,
        ]));

        $this->assertSame('Pending', $purchaseOrder->fresh()->status);
        $this->assertSame(14, (int) $first->fresh()->stock_quantity);
        $this->assertSame(6, (int) $second->fresh()->stock_quantity);

        $this->actingAs($this->clerk)
            ->post(route('deliveries.store'), $this->deliveryPayload($purchaseOrder, [
                $firstItem->po_item_id => 6,
                $secondItem->po_item_id => 2,
            ]))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->assertSame('Delivered', $purchaseOrder->fresh()->status);
        $this->assertSame(10, (int) $firstItem->fresh()->quantity_received);
        $this->assertSame(3, (int) $secondItem->fresh()->quantity_received);
        $this->assertSame(20, (int) $first->fresh()->stock_quantity);
        $this->assertSame(8, (int) $second->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_transactions', 4);
    }

    public function test_the_service_can_receive_all_remaining_units_for_a_purchase_order(): void
    {
        $product = $this->product('Claw Hammer', stock: 5);
        $purchaseOrder = $this->purchaseOrder([[$product, 3]]);

        $received = app(ProcurementService::class)->receive($purchaseOrder);

        $this->assertSame('Delivered', $received->status);
        $this->assertSame(3, (int) $purchaseOrder->items->first()->fresh()->quantity_received);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id,
            'transaction_type' => 'IN',
            'quantity' => 3,
            'reference_type' => 'PO',
            'reference_id' => $purchaseOrder->po_id,
        ]);
    }
}
