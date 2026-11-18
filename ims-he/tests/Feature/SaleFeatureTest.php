<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['category_name' => 'POS Tools']);
    }

    private function cashier(string $role = User::EMPLOYEE, bool $withEmployee = true): User
    {
        $employee = $withEmployee ? Employee::create([
            'first_name' => 'POS',
            'last_name' => 'Cashier',
            'position' => 'Cashier',
            'daily_rate' => 500,
            'status' => 'Active',
        ]) : null;

        return User::create([
            'employee_id' => $employee?->employee_id,
            'email' => strtolower(str_replace(' ', '', $role)).'@example.com',
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function product(string $name, float $price = 100, int $stock = 10, int $reorder = 0): Product
    {
        return Product::create([
            'category_id' => $this->category->category_id,
            'product_name' => $name,
            'brand' => 'POS Brand',
            'unit_price' => $price,
            'stock_quantity' => $stock,
            'reorder_level' => $reorder,
            'is_active' => true,
        ]);
    }

    public function test_sale_creates_all_records_deducts_stock_and_records_income(): void
    {
        $product = $this->product('POS Hammer', 100, 10);
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('sales.store'), [
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
                ['product_id' => $product->product_id, 'quantity' => 1],
            ],
            'payment_method' => 'Cash',
            'amount_paid' => '350',
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('In-store', $invoice->channel);
        $this->assertSame('Completed', $invoice->status);
        $this->assertSame($cashier->employee_id, $invoice->employee_id);
        $this->assertSame(7, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, InvoiceItem::where('invoice_id', $invoice->invoice_id)->count());
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->invoice_id, 'quantity' => 3, 'subtotal' => 300]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->invoice_id, 'amount_paid' => 350, 'change_amount' => 50, 'payment_method' => 'Cash']);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->product_id, 'transaction_type' => 'OUT', 'quantity' => 3,
            'reference_type' => 'Invoice', 'reference_id' => $invoice->invoice_id,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Income', 'amount' => 300, 'reference_type' => 'Invoice', 'reference_id' => $invoice->invoice_id,
        ]);
    }

    public function test_insufficient_stock_and_too_little_money_save_nothing(): void
    {
        $product = $this->product('Limited Hammer', 100, 1);
        $cashier = $this->cashier();

        $this->actingAs($cashier)->post(route('sales.store'), [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => '200',
        ])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('ledger_entries', 0);

        $product->update(['stock_quantity' => 10]);
        $this->actingAs($cashier)->post(route('sales.store'), [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => '100',
        ])->assertSessionHasErrors('amount_paid');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_second_stock_failure_rolls_back_the_entire_sale(): void
    {
        $first = $this->product('First Hammer');
        $second = $this->product('Second Hammer');
        $cashier = $this->cashier();
        $inventory = Mockery::mock(InventoryService::class);
        $inventory->shouldReceive('deduct')->twice()
            ->andReturnUsing(function (int $productId) use ($first, $second) {
                if ($productId === $second->product_id) {
                    throw ValidationException::withMessages(['items' => 'Second stock deduction failed.']);
                }

                return $first;
            });
        $this->app->instance(InventoryService::class, $inventory);

        $this->expectException(ValidationException::class);
        try {
            app(SaleService::class)->sell([
                ['product_id' => $first->product_id, 'quantity' => 1],
                ['product_id' => $second->product_id, 'quantity' => 1],
            ], 'Cash', 200, $cashier);
        } finally {
            $this->assertDatabaseCount('invoices', 0);
            $this->assertDatabaseCount('invoice_items', 0);
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('ledger_entries', 0);
        }
    }

    public function test_reaching_reorder_level_creates_an_automatic_purchase_order(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'POS Supplier',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
        $product = $this->product('Reorder Hammer', 100, 2, 1);
        $product->update(['supplier_id' => $supplier->supplier_id]);

        app(SaleService::class)->sell([
            ['product_id' => $product->product_id, 'quantity' => 1],
        ], 'GCash', 100, $this->cashier());

        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->supplier_id, 'status' => 'Pending']);
    }

    public function test_sales_roles_and_receipt_change(): void
    {
        $employee = $this->cashier();
        $supervisor = $this->cashier(User::STORE_SUPERVISOR, false);

        $this->actingAs($employee)->get(route('sales.create'))->assertOk();
        $this->actingAs($employee)->get(route('sales.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('sales.create'))->assertOk();
        $this->actingAs($supervisor)->get(route('sales.index'))->assertOk();

        foreach ([User::PRESIDENT, User::TREASURER, User::ADMINISTRATOR, User::INVENTORY_CLERK] as $role) {
            $this->actingAs($this->cashier($role, false))->get(route('sales.create'))->assertForbidden();
        }

        $product = $this->product('Receipt Hammer', 125, 5);
        $this->actingAs($employee)->post(route('sales.store'), [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => '300',
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->actingAs($employee)->get(route('sales.index'))
            ->assertOk()
            ->assertSee('#'.$invoice->invoice_id);
        $this->actingAs($employee)->get(route('sales.show', $invoice))
            ->assertOk()
            ->assertSee('₱50.00')
            ->assertSee('Cash');
    }
}
