<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::create([
            'username' => 'report-user',
            'password' => 'secret123',
            'role' => User::EMPLOYEE,
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

    public function test_sales_report_downloads_a_pdf_for_the_selected_date_range(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.sales.download', ['start_date' => '2026-05-01', 'end_date' => '2026-05-31']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=sales-report-2026-05-01-to-2026-05-31.pdf');
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

    public function test_inventory_report_downloads_a_pdf(): void
    {
        $this->actingAs($this->staff())
            ->get(route('reports.inventory.download'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=inventory-report.pdf');
    }
}
