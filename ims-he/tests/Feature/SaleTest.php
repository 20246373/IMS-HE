<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['category_name' => 'Hardware']);
    }

    private function staff(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'username' => "staff{$n}",
            'email'    => "staff{$n}@example.com",
            'password' => 'secret123',
            'role'     => $role,
            'status'   => User::STATUS_ACTIVE,
        ]);
    }

    private function product(int $stock = 10, float $price = 100): Product
    {
        return Product::create([
            'category_id'    => $this->category->category_id,
            'product_name'   => 'Item ' . uniqid(),
            'unit_price'     => $price,
            'stock_quantity' => $stock,
            'reorder_level'  => 0,
            'is_active'      => true,
        ]);
    }

    private function salePayload(Product $product, int $qty = 1, float $paid = 100, string $method = 'Cash'): array
    {
        return [
            'items'          => [['product_id' => $product->product_id, 'quantity' => $qty]],
            'amount_paid'    => $paid,
            'payment_method' => $method,
        ];
    }

    public function test_employee_and_store_supervisor_can_open_sales_pages(): void
    {
        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/sales/create')->assertOk();
            $this->actingAs($user)->get('/sales')->assertOk();
        }
    }

    public function test_other_roles_get_403_on_all_sales_routes(): void
    {
        // An existing invoice so route-model binding doesn't 404 before the role check.
        $invoice = Invoice::create([
            'invoice_date' => today(), 'channel' => 'In-store', 'status' => 'Completed',
        ]);

        foreach ([User::PRESIDENT, User::TREASURER, User::INVENTORY_CLERK, User::ADMINISTRATOR] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/sales/create')->assertForbidden();
            $this->actingAs($user)->post('/sales', [])->assertForbidden();
            $this->actingAs($user)->get('/sales')->assertForbidden();
            $this->actingAs($user)->get('/sales/' . $invoice->invoice_id)->assertForbidden();
        }
    }

    public function test_pending_users_are_sent_to_the_waiting_page(): void
    {
        // RoleMiddleware sends self-registered Pending users to /pending on every staff module.
        $pending = $this->staff(User::PENDING);
        $invoice = Invoice::create([
            'invoice_date' => today(), 'channel' => 'In-store', 'status' => 'Completed',
        ]);

        $this->actingAs($pending)->get('/sales/create')->assertRedirect(route('staff.pending'));
        $this->actingAs($pending)->post('/sales', [])->assertRedirect(route('staff.pending'));
        $this->actingAs($pending)->get('/sales')->assertRedirect(route('staff.pending'));
        $this->actingAs($pending)->get('/sales/' . $invoice->invoice_id)->assertRedirect(route('staff.pending'));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $invoice = Invoice::create([
            'invoice_date' => today(), 'channel' => 'In-store', 'status' => 'Completed',
        ]);

        $this->get('/sales/create')->assertRedirect(route('login'));
        $this->get('/sales')->assertRedirect(route('login'));
        $this->get('/sales/' . $invoice->invoice_id)->assertRedirect(route('login'));
        $this->post('/sales', [])->assertRedirect(route('login'));
    }

    public function test_cash_sale_saves_invoice_items_payment_and_deducts_stock(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 10, price: 100);

        $response = $this->actingAs($user)->post('/sales', $this->salePayload($product, qty: 2, paid: 250));

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('sales.show', $invoice->invoice_id))
            ->assertSessionHas('status');
        $this->assertEquals(200.0, (float) $invoice->total_amount);
        $this->assertEquals('In-store', $invoice->channel);
        $this->assertEquals('Completed', $invoice->status);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $product->product_id,
            'quantity'   => 2,
            'subtotal'   => 200,
        ]);
        $this->assertDatabaseHas('payments', [
            'invoice_id'     => $invoice->invoice_id,
            'amount_paid'    => 250,
            'change_amount'  => 50,
            'payment_method' => 'Cash',
        ]);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id'       => $product->product_id,
            'transaction_type' => 'OUT',
            'quantity'         => 2,
            'reference_type'   => 'Invoice',
            'reference_id'     => $invoice->invoice_id,
        ]);
    }

    public function test_gcash_sale_works_the_same_way(): void
    {
        $user = $this->staff(User::STORE_SUPERVISOR);
        $product = $this->product(stock: 5, price: 80);

        $this->actingAs($user)->post('/sales', $this->salePayload($product, qty: 3, paid: 240, method: 'GCash'))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertEquals(240.0, (float) $invoice->total_amount);
        $this->assertEquals(0.0, (float) Payment::firstOrFail()->change_amount);
        $this->assertEquals('GCash', Payment::firstOrFail()->payment_method);
        $this->assertEquals(2, $product->fresh()->stock_quantity);
    }

    public function test_quantity_above_stock_is_rejected_with_no_sale_and_no_stock_change(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 5, price: 100);

        $this->actingAs($user)->post('/sales', $this->salePayload($product, qty: 6, paid: 1000))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    public function test_amount_paid_below_total_is_rejected_with_error_alert(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 10, price: 100);

        $this->actingAs($user)->post('/sales', $this->salePayload($product, qty: 2, paid: 150))
            ->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertEquals(10, $product->fresh()->stock_quantity);

        // The flashed errors render through the shared <x-alert> component.
        $this->get('/dashboard')->assertSee('Amount paid is less than the total.');
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product();

        $this->actingAs($user)->post('/sales', $this->salePayload($product, paid: 100, method: 'Card'))
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_missing_items_are_rejected(): void
    {
        $user = $this->staff(User::EMPLOYEE);

        $this->actingAs($user)->post('/sales', [
            'amount_paid'    => 100,
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_duplicate_product_lines_are_merged_before_stock_check_and_deduction(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 10, price: 100);

        // Same product_id submitted twice: merged into one line of qty 5.
        $this->actingAs($user)->post('/sales', [
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
                ['product_id' => $product->product_id, 'quantity' => 3],
            ],
            'amount_paid'    => 500,
            'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertEquals(1, InvoiceItem::count());
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $product->product_id,
            'quantity'   => 5,
            'subtotal'   => 500,
        ]);
        $this->assertEquals(500.0, (float) $invoice->total_amount);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    public function test_failure_after_writes_rolls_back_sale_items_stock_and_shows_error(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $sold = $this->product(stock: 10, price: 100);
        $target = $this->product(stock: 10, price: 100);

        // Pre-existing committed rows, so "unchanged" means unchanged, not empty.
        // These are created directly: an HTTP sale here would make Laravel cache
        // the SaleController (with the real SaleService) on the route object, and
        // rebinding the service afterwards would have no effect on the next POST.
        $existing = Invoice::create([
            'invoice_date' => today(), 'channel' => 'In-store',
            'status' => 'Completed', 'total_amount' => 100,
        ]);
        $existing->items()->create([
            'product_id' => $sold->product_id, 'quantity' => 1,
            'price' => 100, 'subtotal' => 100,
        ]);
        Payment::create([
            'invoice_id' => $existing->invoice_id, 'payment_date' => today(),
            'amount_paid' => 100, 'change_amount' => 0, 'payment_method' => 'Cash',
        ]);
        $sold->decrement('stock_quantity', 1);

        $invoicesBefore = Invoice::count();
        $itemsBefore = InvoiceItem::count();
        $paymentsBefore = Payment::count();
        $soldStockBefore = $sold->fresh()->stock_quantity;
        $targetStockBefore = $target->fresh()->stock_quantity;

        // A checkout that completes all writes and then throws inside the
        // transaction: everything must roll back.
        $mock = new class extends SaleService {
            public bool $called = false;

            public function __construct()
            {
                parent::__construct(app(InventoryService::class));
            }

            public function checkout(array $items, float $amountPaid, string $method, ?int $customerId, ?int $userId): Invoice
            {
                $this->called = true;

                return DB::transaction(function () use ($items, $amountPaid, $method, $customerId, $userId) {
                    parent::checkout($items, $amountPaid, $method, $customerId, $userId);

                    throw ValidationException::withMessages(['items' => 'Simulated failure after checkout.']);
                });
            }
        };
        $this->app->instance(SaleService::class, $mock);

        $response = $this->actingAs($user)->post('/sales', $this->salePayload($target, qty: 2, paid: 250));

        $this->assertTrue($mock->called, 'The failing SaleService was not invoked.');
        $response->assertSessionHasErrors('items');
        $this->assertStringNotContainsString('/sales/', (string) $response->headers->get('Location'));

        $this->assertEquals($invoicesBefore, Invoice::count());
        $this->assertEquals($itemsBefore, InvoiceItem::count());
        $this->assertEquals($paymentsBefore, Payment::count());
        $this->assertEquals($soldStockBefore, $sold->fresh()->stock_quantity);
        $this->assertEquals($targetStockBefore, $target->fresh()->stock_quantity);

        // The flashed errors render through the shared <x-alert> component.
        $this->get('/dashboard')->assertSee('Simulated failure after checkout.');
    }

    public function test_show_displays_the_invoice_for_an_authorized_user(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 10, price: 100);

        $this->actingAs($user)->post('/sales', $this->salePayload($product, qty: 1, paid: 100));
        $invoice = Invoice::firstOrFail();

        $this->actingAs($user)->get('/sales/' . $invoice->invoice_id)
            ->assertOk()
            ->assertSee('Invoice #' . $invoice->invoice_id)
            ->assertSee($product->product_name);
    }
}
