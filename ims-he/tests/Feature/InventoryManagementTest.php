<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'username' => 'inventory-user',
            'password' => 'secret123',
            'role' => User::INVENTORY_CLERK,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->category = Category::create(['category_name' => 'Tools']);
    }

    private function product(string $name, int $stock = 5, int $reorder = 2): Product
    {
        return Product::create([
            'category_id' => $this->category->category_id,
            'product_name' => $name,
            'unit_price' => 100,
            'stock_quantity' => $stock,
            'reorder_level' => $reorder,
            'is_active' => true,
        ]);
    }

    public function test_categories_can_be_created_edited_and_soft_deleted(): void
    {
        $this->actingAs($this->staff)
            ->post(route('categories.store'), ['category_name' => 'Fasteners'])
            ->assertRedirect(route('categories.index'));

        $category = Category::where('category_name', 'Fasteners')->firstOrFail();

        $this->put(route('categories.update', $category), ['category_name' => 'Bolts'])
            ->assertRedirect(route('categories.index'));
        $this->patch(route('categories.toggle', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'category_id' => $category->category_id,
            'category_name' => 'Bolts',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('categories', ['category_id' => $category->category_id]);
    }

    public function test_products_can_be_created_edited_and_soft_deleted(): void
    {
        $this->actingAs($this->staff)
            ->post(route('products.store'), [
                'product_name' => 'Claw Hammer',
                'category_id' => $this->category->category_id,
                'unit_price' => 250,
                'stock_quantity' => 6,
                'reorder_level' => 3,
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::where('product_name', 'Claw Hammer')->firstOrFail();
        $this->assertSame(6, (int) $product->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id,
            'transaction_type' => 'IN',
            'quantity' => 6,
            'reference_type' => 'Adjustment',
            'reference_id' => $product->product_id,
        ]);

        $this->put(route('products.update', $product), [
            'product_name' => 'Claw Hammer 16oz',
            'category_id' => $this->category->category_id,
            'unit_price' => 275,
            'stock_quantity' => 4,
            'reorder_level' => 2,
        ])->assertRedirect(route('products.index'));

        $this->patch(route('products.toggle', $product->fresh()))->assertRedirect();
        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'product_name' => 'Claw Hammer 16oz',
            'stock_quantity' => 4,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id,
            'transaction_type' => 'OUT',
            'quantity' => 2,
            'reference_type' => 'Adjustment',
            'reference_id' => $product->product_id,
        ]);
    }

    public function test_inventory_service_logs_movements_and_blocks_negative_stock(): void
    {
        $product = $this->product('Safety Goggles', stock: 3);
        $inventory = app(InventoryService::class);

        $inventory->deduct($product->product_id, 2, 'Test', 1);
        $inventory->add($product->product_id, 4, 'Test', 2);

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertSame(['OUT', 'IN'], InventoryTransaction::orderBy('transaction_id')
            ->pluck('transaction_type')->all());

        try {
            $inventory->deduct($product->product_id, 6, 'Test', 3);
            $this->fail('Deducting more than available stock must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_transactions', 2);
    }

    public function test_stock_page_highlights_low_stock_and_dashboard_shows_count(): void
    {
        $this->product('Low stock item', stock: 2, reorder: 2);
        $this->product('Healthy item', stock: 5, reorder: 2);

        $this->actingAs($this->staff)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Stock quantity')
            ->assertSee('Reorder level')
            ->assertSee('Low stock item')
            ->assertSee('lowrow');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Low-stock products')
            ->assertSee('(1)');
    }
}
