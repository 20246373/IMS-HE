<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = User::STORE_SUPERVISOR): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'username' => "report-user-{$number}",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function invoice(string $date, float $total, bool $paid): Invoice
    {
        $invoice = Invoice::create([
            'invoice_date' => $date,
            'channel' => 'POS',
            'status' => $paid ? 'Completed' : 'Pending',
            'total_amount' => $total,
        ]);

        if ($paid) {
            Payment::create([
                'invoice_id' => $invoice->invoice_id,
                'payment_date' => $date,
                'amount_paid' => $total,
                'change_amount' => 0,
                'payment_method' => 'Cash',
            ]);
        }

        return $invoice;
    }

    public function test_sales_report_totals_paid_invoices_in_the_selected_date_range(): void
    {
        $this->actingAs($this->staff());
        $inside = $this->invoice('2026-05-10', 120.50, true);
        $this->invoice('2026-05-11', 79.50, true);
        $this->invoice('2026-05-12', 900, false);
        $this->invoice('2026-04-30', 500, true);

        $this->get(route('reports.sales', ['start_date' => '2026-05-01', 'end_date' => '2026-05-31']))
            ->assertOk()
            ->assertSee('200.00')
            ->assertSee('2', false)
            ->assertSee('#'.$inside->invoice_id)
            ->assertSee('Save as PDF')
            ->assertSee(route('reports.sales.download', ['start_date' => '2026-05-01', 'end_date' => '2026-05-31']))
            ->assertDontSee('900.00');
    }

    public function test_sales_report_filters_completed_sales_by_category_and_shows_category_totals(): void
    {
        $tools = Category::create(['category_name' => 'Report Tools']);
        $hardware = Category::create(['category_name' => 'Report Hardware']);
        $hammer = Product::create([
            'category_id' => $tools->category_id,
            'product_name' => 'Report Hammer',
            'unit_price' => 100,
            'stock_quantity' => 10,
            'reorder_level' => 0,
        ]);
        $drill = Product::create([
            'category_id' => $hardware->category_id,
            'product_name' => 'Report Drill',
            'unit_price' => 250,
            'stock_quantity' => 10,
            'reorder_level' => 0,
        ]);
        $invoice = $this->invoice('2026-05-10', 350, true);
        InvoiceItem::create([
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $hammer->product_id,
            'quantity' => 1,
            'price' => 100,
            'subtotal' => 100,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $drill->product_id,
            'quantity' => 1,
            'price' => 250,
            'subtotal' => 250,
        ]);
        $pending = $this->invoice('2026-05-11', 400, false);
        Payment::create([
            'invoice_id' => $pending->invoice_id,
            'payment_date' => '2026-05-11',
            'amount_paid' => 400,
            'change_amount' => 0,
            'payment_method' => 'Cash',
        ]);

        $this->actingAs($this->staff())->get(route('reports.sales', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'category_id' => $tools->category_id,
        ]))->assertOk()
            ->assertSee('₱100.00')
            ->assertSee('Report Tools')
            ->assertDontSee('₱250.00')
            ->assertDontSee('₱400.00');
    }

    public function test_sales_report_downloads_a_pdf_for_the_selected_date_range(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.sales.download', ['start_date' => '2026-05-01', 'end_date' => '2026-05-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=sales-report-2026-05-01-to-2026-05-31.pdf');
    }

    public function test_sales_report_pdf_keeps_the_category_filter(): void
    {
        $category = Category::create(['category_name' => 'PDF Tools']);

        $this->actingAs($this->staff())
            ->get(route('reports.sales.download', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
                'category_id' => $category->category_id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_sales_report_rejects_end_date_before_start_date(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.sales', ['start_date' => '2026-05-31', 'end_date' => '2026-05-01']))
            ->assertSessionHasErrors('end_date');
    }

    public function test_inventory_report_shows_stock_alerts_and_stock_valuation(): void
    {
        $this->actingAs($this->staff());
        $category = Category::create(['category_name' => 'Tools']);
        Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Hammer',
            'unit_price' => 100,
            'stock_quantity' => 3,
            'reorder_level' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Drill',
            'unit_price' => 250,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_active' => false,
        ]);

        $this->get(route('reports.inventory'))
            ->assertOk()
            ->assertSee('Total stock valuation')
            ->assertSee('800.00')
            ->assertSee('Hammer')
            ->assertSee('Drill')
            ->assertSee('Low stock')
            ->assertSee('Save as PDF')
            ->assertSee(route('reports.inventory.download'));
    }

    public function test_inventory_report_filters_the_table_and_totals_by_category(): void
    {
        $tools = Category::create(['category_name' => 'Tools']);
        $hardware = Category::create(['category_name' => 'Hardware']);

        Product::create([
            'category_id' => $tools->category_id,
            'product_name' => 'Hammer',
            'unit_price' => 100,
            'stock_quantity' => 3,
            'reorder_level' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $hardware->category_id,
            'product_name' => 'Drill',
            'unit_price' => 250,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->staff())
            ->get(route('reports.inventory', ['category_id' => $tools->category_id]))
            ->assertOk()
            ->assertSee('Hammer')
            ->assertDontSee('Drill')
            ->assertSee('300.00')
            ->assertDontSee('800.00')
            ->assertSee(route('reports.inventory.download', ['category_id' => $tools->category_id]));

        $this->get(route('reports.inventory.download', ['category_id' => $tools->category_id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_procurement_report_filters_purchase_orders_by_date_supplier_and_category(): void
    {
        $tools = Category::create(['category_name' => 'Tools']);
        $hardware = Category::create(['category_name' => 'Hardware']);
        $preferredSupplier = Supplier::create([
            'supplier_name' => 'Preferred Supplier',
            'contact_number' => '09170000001',
            'address' => 'Main Street',
        ]);
        $otherSupplier = Supplier::create([
            'supplier_name' => 'Other Supplier',
            'contact_number' => '09170000002',
            'address' => 'Side Street',
        ]);

        $hammer = Product::create([
            'category_id' => $tools->category_id,
            'supplier_id' => $preferredSupplier->supplier_id,
            'product_name' => 'Hammer',
            'unit_price' => 100,
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'is_active' => true,
        ]);
        $saw = Product::create([
            'category_id' => $tools->category_id,
            'supplier_id' => $otherSupplier->supplier_id,
            'product_name' => 'Saw',
            'unit_price' => 70,
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'is_active' => true,
        ]);
        $drill = Product::create([
            'category_id' => $hardware->category_id,
            'supplier_id' => $preferredSupplier->supplier_id,
            'product_name' => 'Drill',
            'unit_price' => 200,
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'is_active' => true,
        ]);
        $wrench = Product::create([
            'category_id' => $tools->category_id,
            'supplier_id' => $preferredSupplier->supplier_id,
            'product_name' => 'Wrench',
            'unit_price' => 50,
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        $matchingOrder = PurchaseOrder::create([
            'supplier_id' => $preferredSupplier->supplier_id,
            'order_date' => '2026-11-10',
            'status' => 'Pending',
        ]);
        PurchaseOrderItem::create([
            'po_id' => $matchingOrder->po_id,
            'product_id' => $hammer->product_id,
            'quantity' => 10,
            'quantity_received' => 4,
            'unit_cost' => 100,
            'subtotal' => 1000,
        ]);
        InventoryTransaction::create([
            'product_id' => $hammer->product_id,
            'transaction_type' => 'IN',
            'quantity' => 4,
            'transaction_date' => '2026-11-12',
            'reference_type' => 'PO',
            'reference_id' => $matchingOrder->po_id,
        ]);

        $otherSupplierOrder = PurchaseOrder::create([
            'supplier_id' => $otherSupplier->supplier_id,
            'order_date' => '2026-11-11',
            'status' => 'Pending',
        ]);
        PurchaseOrderItem::create([
            'po_id' => $otherSupplierOrder->po_id,
            'product_id' => $saw->product_id,
            'quantity' => 10,
            'quantity_received' => 0,
            'unit_cost' => 70,
            'subtotal' => 700,
        ]);

        $otherCategoryOrder = PurchaseOrder::create([
            'supplier_id' => $preferredSupplier->supplier_id,
            'order_date' => '2026-11-12',
            'status' => 'Pending',
        ]);
        PurchaseOrderItem::create([
            'po_id' => $otherCategoryOrder->po_id,
            'product_id' => $drill->product_id,
            'quantity' => 10,
            'quantity_received' => 0,
            'unit_cost' => 200,
            'subtotal' => 2000,
        ]);

        $outsideRangeOrder = PurchaseOrder::create([
            'supplier_id' => $preferredSupplier->supplier_id,
            'order_date' => '2026-10-31',
            'status' => 'Pending',
        ]);
        PurchaseOrderItem::create([
            'po_id' => $outsideRangeOrder->po_id,
            'product_id' => $wrench->product_id,
            'quantity' => 10,
            'quantity_received' => 0,
            'unit_cost' => 50,
            'subtotal' => 500,
        ]);

        $filters = [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'supplier_id' => $preferredSupplier->supplier_id,
            'category_id' => $tools->category_id,
        ];

        $this->actingAs($this->staff(User::INVENTORY_CLERK))
            ->get(route('reports.procurement', $filters))
            ->assertOk()
            ->assertSee('Procurement report')
            ->assertSee('Hammer')
            ->assertDontSee('Saw')
            ->assertDontSee('Drill')
            ->assertDontSee('Wrench')
            ->assertSee('1,000.00')
            ->assertDontSee('2,000.00')
            ->assertDontSee('700.00')
            ->assertDontSee('500.00')
            ->assertSee(route('reports.procurement.download', $filters));

        $this->get(route('reports.procurement.download', $filters))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'attachment; filename=procurement-report-2026-11-01-to-2026-11-30.pdf'
            );
    }

    public function test_procurement_report_is_restricted_to_president_and_inventory_clerk(): void
    {
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get(route('reports.procurement'))
            ->assertForbidden();

        $this->actingAs($this->staff(User::PRESIDENT))
            ->get(route('reports.procurement'))
            ->assertOk();
    }

    public function test_inventory_report_downloads_a_pdf(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.inventory.download'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=inventory-report.pdf');
    }
}
