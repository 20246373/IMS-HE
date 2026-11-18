<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\LedgerService;
use App\Services\OrderManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class LedgerFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'email' => "ledger{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function onlineOrder(): Invoice
    {
        $category = Category::create(['category_name' => 'Ledger Tools']);
        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Ledger Hammer',
            'unit_price' => 125,
            'stock_quantity' => 5,
            'reorder_level' => 0,
        ]);
        $customer = Customer::create([
            'email' => 'ledger-customer@example.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'first_name' => 'Ledger',
            'last_name' => 'Customer',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
        app(CartService::class)->add($customer, $product, 2);

        return app(CheckoutService::class)->placeOrder(
            $customer,
            $customer->only(['first_name', 'last_name', 'contact_number', 'address']),
            'Cash',
        );
    }

    public function test_online_completion_records_one_income_and_repeating_action_does_not_duplicate_it(): void
    {
        $invoice = $this->onlineOrder();
        $staff = $this->staff(User::EMPLOYEE);

        app(OrderManagementService::class)->complete($invoice, $staff);
        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Income',
            'amount' => 250,
            'reference_type' => 'Invoice',
            'reference_id' => $invoice->invoice_id,
        ]);

        $this->expectException(ValidationException::class);
        app(OrderManagementService::class)->complete($invoice, $staff);
        $this->assertSame(1, LedgerEntry::where('reference_id', $invoice->invoice_id)->count());
    }

    public function test_delivery_records_one_expense_for_received_quantities(): void
    {
        $category = Category::create(['category_name' => 'Delivery Tools']);
        $supplier = Supplier::create([
            'supplier_name' => 'Ledger Supplier',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'supplier_id' => $supplier->supplier_id,
            'product_name' => 'Delivery Drill',
            'unit_price' => 30,
            'stock_quantity' => 0,
            'reorder_level' => 0,
        ]);
        $po = PurchaseOrder::create(['supplier_id' => $supplier->supplier_id, 'order_date' => today(), 'status' => 'Pending']);
        $item = PurchaseOrderItem::create([
            'po_id' => $po->po_id, 'product_id' => $product->product_id, 'quantity' => 4,
            'quantity_received' => 0, 'unit_cost' => 17.50, 'subtotal' => 70,
        ]);

        $this->actingAs($this->staff(User::INVENTORY_CLERK))
            ->post(route('deliveries.store'), [
                'purchase_order_id' => $po->po_id,
                'items' => [['po_item_id' => $item->po_item_id, 'quantity_received' => 3]],
            ])->assertRedirect();

        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Expense',
            'amount' => 52.50,
            'description' => "Delivery - PO #{$po->po_id}",
            'reference_type' => 'PO',
            'reference_id' => $po->po_id,
        ]);
    }

    public function test_manual_entries_validate_and_reversals_are_single_use(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $response = $this->actingAs($treasurer)->post(route('ledger.store'), [
            'entry_type' => 'Expense', 'amount' => 'invalid', 'description' => '',
        ]);
        $response->assertSessionHasErrors(['amount', 'description']);
        $this->assertDatabaseCount('ledger_entries', 0);

        $this->actingAs($treasurer)->post(route('ledger.store'), [
            'entry_type' => 'Expense', 'amount' => '₱1,234.50', 'description' => 'Office supplies',
        ])->assertRedirect(route('ledger.index'));
        $entry = LedgerEntry::firstOrFail();

        $this->actingAs($treasurer)->post(route('ledger.reverse', $entry))->assertRedirect();
        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Income',
            'amount' => 1234.50,
            'description' => "Reversal of ledger #{$entry->ledger_id}",
            'reference_type' => null,
        ]);
        $this->actingAs($treasurer)->post(route('ledger.reverse', $entry))
            ->assertSessionHasErrors('ledger');
        $reversal = LedgerEntry::where('description', "Reversal of ledger #{$entry->ledger_id}")->firstOrFail();
        $this->actingAs($treasurer)->post(route('ledger.reverse', $reversal))
            ->assertSessionHasErrors('ledger');
    }

    public function test_ledger_access_filters_totals_and_roles(): void
    {
        LedgerEntry::create(['entry_date' => '2026-11-10', 'entry_type' => 'Income', 'amount' => 100, 'description' => 'Sale']);
        LedgerEntry::create(['entry_date' => '2026-11-11', 'entry_type' => 'Expense', 'amount' => 40, 'description' => 'Cost']);
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get(route('ledger.index', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']))
            ->assertOk()->assertSee('₱100.00')->assertSee('₱40.00')->assertSee('₱60.00');
        $this->actingAs($this->staff(User::ADMINISTRATOR))->get(route('ledger.index'))->assertForbidden();
        $this->actingAs($this->staff(User::EMPLOYEE))->get(route('ledger.create'))->assertForbidden();
    }

    public function test_financial_report_and_pdf_are_filtered_and_role_protected(): void
    {
        LedgerEntry::create(['entry_date' => '2026-11-10', 'entry_type' => 'Income', 'amount' => 100, 'description' => 'Sale']);
        LedgerEntry::create(['entry_date' => '2026-11-10', 'entry_type' => 'Expense', 'amount' => 40, 'description' => 'Cost']);
        $this->actingAs($this->staff(User::PRESIDENT))
            ->get(route('reports.financial', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']))
            ->assertOk()->assertSee('₱100.00')->assertSee('₱40.00')->assertSee('₱60.00');
        $this->get(route('reports.financial.download', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->staff(User::EMPLOYEE))->get(route('reports.financial'))->assertForbidden();
    }

    public function test_reconcile_reports_a_mismatch(): void
    {
        Invoice::create(['invoice_date' => '2026-11-10', 'channel' => 'Online', 'status' => 'Completed', 'total_amount' => 100]);
        Log::spy();

        $this->artisan('ledger:reconcile', ['date' => '2026-11-10'])
            ->expectsOutput('Result: Mismatch')
            ->assertSuccessful();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_ledger_failure_rolls_back_order_completion(): void
    {
        $invoice = $this->onlineOrder();
        $staff = $this->staff(User::EMPLOYEE);
        $mock = Mockery::mock(LedgerService::class);
        $mock->shouldReceive('recordSale')->once()->andThrow(new \RuntimeException('ledger failed'));
        $this->app->instance(LedgerService::class, $mock);

        try {
            app(OrderManagementService::class)->complete($invoice, $staff);
        } catch (\RuntimeException) {
        }

        $this->assertSame('Pending', $invoice->fresh()->status);
        $this->assertDatabaseCount('ledger_entries', 0);
    }
}
