<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesPayrollData;
use Tests\TestCase;

/** Task B5: printable payslip, payslip PDF, payroll report and report PDF. */
class PayslipReportTest extends TestCase
{
    use CreatesPayrollData, RefreshDatabase;

    private User $treasurer;
    private Payroll $payroll;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 09:00:00');
        $this->treasurer = $this->staff(User::TREASURER);

        $juan = $this->employee(['first_name' => 'Juan', 'last_name' => 'Cruz', 'daily_rate' => 1000, 'sss_no' => '12-3456789-0']);
        $ana = $this->employee(['first_name' => 'Ana', 'last_name' => 'Lopez', 'daily_rate' => 800]);
        $this->attend($juan, '2026-11-01', '2026-11-15');
        $this->attend($ana, '2026-11-01', '2026-11-10');

        $this->payroll = app(PayrollService::class)->run(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-15'), $this->treasurer->user_id);
    }

    private function urls(): array
    {
        $slip = $this->payroll->payslips()->first();

        return [
            "/payslips/{$slip->payslip_id}",
            "/payslips/{$slip->payslip_id}/pdf",
            "/payrolls/{$this->payroll->payroll_id}/report",
            "/payrolls/{$this->payroll->payroll_id}/report/pdf",
        ];
    }

    public function test_president_and_treasurer_can_open_every_document(): void
    {
        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $user = $this->staff($role);
            foreach ($this->urls() as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }

    public function test_other_roles_are_forbidden_and_guests_sent_to_login(): void
    {
        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR, User::INVENTORY_CLERK, 'Administrator'] as $role) {
            $user = $this->staff($role);
            foreach ($this->urls() as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }

        $this->assertPendingIsSentToWaitingPage($this->urls()[0]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach ($this->urls() as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_payslip_page_shows_the_employee_numbers_in_peso_format(): void
    {
        $slip = $this->payroll->payslips()->with('employee')->get()->firstWhere('employee.last_name', 'Cruz');

        $page = $this->actingAs($this->treasurer)->get("/payslips/{$slip->payslip_id}");
        $page->assertSee('Juan Cruz')->assertSee('12-3456789-0')
            ->assertSee('Nov 01, 2026')->assertSee('Nov 15, 2026')
            ->assertSee('₱15,000.00')                  // gross: 15 days x 1,000
            ->assertSee('₱750.00')                     // SSS (monthly 30,000 -> MSC 30,000 x 5% / 2)
            ->assertSee('NET PAY')->assertSee('DRAFT')
            ->assertSee('Download PDF')->assertSee('window.print()', false);
        $page->assertSee('₱' . number_format($slip->net_pay, 2));

        app(PayrollService::class)->finalize($this->payroll, $this->treasurer->user_id);
        $this->actingAs($this->treasurer)->get("/payslips/{$slip->payslip_id}")->assertSee('FINAL')->assertDontSee('DRAFT');
    }

    public function test_payslip_pdf_is_a_real_pdf_download(): void
    {
        $slip = $this->payroll->payslips()->first();

        $response = $this->actingAs($this->treasurer)->get("/payslips/{$slip->payslip_id}/pdf")->assertOk();

        $this->assertStringStartsWith('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="payslip-', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_report_shows_period_totals(): void
    {
        $p = $this->payroll->refresh();
        $slips = $p->payslips;

        $page = $this->actingAs($this->treasurer)->get("/payrolls/{$p->payroll_id}/report")->assertOk();
        $page->assertSee('PAYROLL REPORT')->assertSee('Nov 01, 2026')->assertSee('Nov 15, 2026')
            ->assertSee('Juan Cruz')->assertSee('Ana Lopez')
            ->assertSee('₱23,000.00')                  // gross: 15 x 1,000 + 10 x 800
            ->assertSee('₱' . number_format($p->total_deductions, 2))
            ->assertSee('₱' . number_format($p->total_net, 2))
            ->assertSee('₱' . number_format($slips->sum('sss_deduction'), 2))
            ->assertSee('Download PDF');

        $this->assertEquals(round($p->total_gross - $p->total_deductions, 2), (float) $p->total_net);
    }

    public function test_report_pdf_is_a_real_pdf_download(): void
    {
        $response = $this->actingAs($this->treasurer)->get("/payrolls/{$this->payroll->payroll_id}/report/pdf")->assertOk();

        $this->assertStringStartsWith('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('payroll-report-2026-11-01-to-2026-11-15.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
