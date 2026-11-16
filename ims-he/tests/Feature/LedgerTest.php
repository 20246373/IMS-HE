<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'username' => "ledger-user-{$n}",
            'email' => "ledger{$n}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function product(int $stock = 10, float $price = 100): Product
    {
        $category = Category::create(['category_name' => 'Cat '.uniqid()]);

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Item '.uniqid(),
            'unit_price' => $price,
            'stock_quantity' => $stock,
            'reorder_level' => 0,
            'is_active' => true,
        ]);
    }

    public function test_pos_sale_writes_an_income_ledger_entry_inside_the_sale(): void
    {
        $user = $this->staff(User::EMPLOYEE);
        $product = $this->product(stock: 10, price: 100);

        $this->actingAs($user)->post('/sales', [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'amount_paid' => 250,
            'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();

        $invoiceId = Invoice::firstOrFail()->invoice_id;

        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Income',
            'amount' => 200,
            'reference_type' => 'Invoice',
            'reference_id' => $invoiceId,
            'recorded_by' => $user->user_id,
        ]);
    }

    public function test_receiving_a_po_delivery_writes_an_expense_ledger_entry(): void
    {
        $receiver = $this->staff(User::INVENTORY_CLERK);
        $this->actingAs($receiver);

        $supplier = Supplier::create(['supplier_name' => 'Supp '.uniqid(), 'contact_number' => '0917', 'address' => 'Baguio']);
        $product = $this->product(stock: 0);

        $po = PurchaseOrder::create(['supplier_id' => $supplier->supplier_id, 'order_date' => today(), 'status' => 'Pending']);
        $item = PurchaseOrderItem::create([
            'po_id' => $po->po_id,
            'product_id' => $product->product_id,
            'quantity' => 10,
            'quantity_received' => 0,
            'unit_cost' => 50,
            'subtotal' => 500,
        ]);

        $this->post('/deliveries', [
            'purchase_order_id' => $po->po_id,
            'items' => [['po_item_id' => $item->po_item_id, 'quantity_received' => 4]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Expense',
            'amount' => 200, // 4 units x 50
            'reference_type' => 'PO',
            'reference_id' => $po->po_id,
            'recorded_by' => $receiver->user_id,
        ]);
    }

    public function test_payroll_finalization_writes_a_payroll_expense_entry(): void
    {
        $treasurer = $this->staff(User::TREASURER);

        $payroll = Payroll::create([
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-15',
            'processed_by' => $treasurer->user_id,
            'status' => 'Draft',
            'total_gross' => 1000,
            'total_deductions' => 100,
            'total_net' => 900,
        ]);

        $this->actingAs($treasurer)->post("/payroll/{$payroll->payroll_id}/finalize")->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ledger_entries', [
            'entry_type' => 'Expense',
            'amount' => 900,
            'reference_type' => 'Payroll',
            'reference_id' => $payroll->payroll_id,
            'recorded_by' => $treasurer->user_id,
        ]);
    }

    public function test_president_and_treasurer_can_log_manual_entries_but_supervisor_cannot(): void
    {
        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $this->actingAs($this->staff($role))->post('/ledger', [
                'entry_type' => 'Income',
                'amount' => 500,
                'description' => 'Manual income test',
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->post('/ledger', ['entry_type' => 'Income', 'amount' => 1, 'description' => 'nope'])
            ->assertForbidden();

        $this->assertSame(2, LedgerEntry::whereNull('reference_type')->count());
    }

    public function test_ledger_index_filters_by_date_and_type_and_shows_balance(): void
    {
        $viewer = $this->staff(User::STORE_SUPERVISOR);
        $financial = app(FinancialService::class);

        $financial->recordIncome('Invoice', 1, 1000, 'ALPHA-INCOME', null);
        $financial->recordExpense('PO', 2, 300, 'BETA-EXPENSE', null);

        $entry = LedgerEntry::latest('ledger_id')->first();
        $entry->update(['entry_date' => '2026-01-15']);

        LedgerEntry::where('reference_type', 'Invoice')->update(['entry_date' => '2026-02-10']);

        $this->actingAs($viewer)->get('/ledger?start_date=2026-01-01&end_date=2026-01-31&entry_type=Expense')
            ->assertOk()
            ->assertSee('BETA-EXPENSE')
            ->assertDontSee('ALPHA-INCOME');

        $this->actingAs($viewer)->get('/ledger?start_date=2026-02-01&end_date=2026-02-28')
            ->assertOk()
            ->assertSee('ALPHA-INCOME')
            ->assertDontSee('BETA-EXPENSE');
    }

    public function test_reconciliation_flags_a_mismatch(): void
    {
        $viewer = $this->staff(User::PRESIDENT);
        $product = $this->product(stock: 10, price: 100);

        // A real sale today: 2 x 100 = 200 income.
        $this->actingAs($this->staff(User::EMPLOYEE))->post('/sales', [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'amount_paid' => 200,
            'payment_method' => 'Cash',
        ]);

        // Reconciled today.
        $this->actingAs($viewer)->get('/ledger/reconcile?date='.today()->toDateString())
            ->assertOk()
            ->assertSee('Reconciled');

        // A manual entry makes recorded income exceed sales -> mismatch.
        app(FinancialService::class)->recordManual('Income', 50, 'extra cash');

        $this->actingAs($viewer)->get('/ledger/reconcile?date='.today()->toDateString())
            ->assertOk()
            ->assertSee('Mismatch');
    }

    public function test_financial_report_is_limited_to_president_and_treasurer(): void
    {
        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $this->actingAs($this->staff($role))->get('/reports/financial')->assertOk();
        }

        foreach ([User::STORE_SUPERVISOR, User::INVENTORY_CLERK, User::EMPLOYEE, User::ADMINISTRATOR] as $role) {
            $this->actingAs($this->staff($role))->get('/reports/financial')->assertForbidden();
        }
    }
}
