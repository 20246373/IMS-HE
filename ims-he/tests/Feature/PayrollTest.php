<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesPayrollData;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use CreatesPayrollData, RefreshDatabase;

    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 09:00:00');
        $this->treasurer = $this->staff(User::TREASURER);
    }

    /** A Draft: one employee at 1,000 a day with one attendance record per date in the period and 2 overtime hours in total. */
    private function draft(string $start = '2026-11-01', string $end = '2026-11-15'): Payroll
    {
        $emp = $this->employee(['daily_rate' => 1000]);
        $this->attend($emp, $start, $end);
        // Two overtime hours in total, recorded on the first day.
        $emp->attendance()->orderBy('work_date')->first()->update(['overtime_hours' => 2]);

        return app(PayrollService::class)->run(Carbon::parse($start), Carbon::parse($end), $this->treasurer->user_id);
    }

    // ---- computing the draft ---------------------------------------------------------------

    public function test_treasurer_runs_a_draft_with_days_overtime_gross_deductions_and_net(): void
    {
        $emp = $this->employee(['daily_rate' => 1000]);
        $this->attend($emp, '2026-11-01', '2026-11-15');
        $emp->attendance()->orderBy('work_date')->first()->update(['overtime_hours' => 2]);

        $this->actingAs($this->treasurer)
            ->post('/payrolls', ['period_start' => '2026-11-01', 'period_end' => '2026-11-15'])
            ->assertRedirect();

        $payroll = Payroll::firstOrFail();
        $this->assertSame('Draft', $payroll->status);
        $this->assertSame($this->treasurer->user_id, $payroll->processed_by);
        $this->assertSame('2026-11-01', $payroll->period_start->toDateString());

        $slip = $payroll->payslips()->firstOrFail();
        $this->assertSame($emp->employee_id, $slip->employee_id);
        $this->assertEquals(15, (float) $slip->days_worked);
        $this->assertEquals(2, (float) $slip->overtime_hours);
        // 15 days x 1,000 + 2 hours x (1,000 / 8) x 1.25 = 15,312.50
        $this->assertEquals(15312.50, (float) $slip->gross_pay);
        // Monthly equivalent 30,625: MSC 30,500 -> SSS 762.50; PhilHealth 30,625 x 2.5% / 2 = 382.81; Pag-IBIG 100.
        $this->assertEquals(762.50, (float) $slip->sss_deduction);
        $this->assertEquals(382.81, (float) $slip->philhealth_deduction);
        $this->assertEquals(100.00, (float) $slip->pagibig_deduction);

        $expected = app(PayrollService::class)->computeDeductions(15312.50);
        $this->assertEquals($expected['tax'], (float) $slip->tax_deduction);
        $this->assertEquals($expected['total'], (float) $slip->total_deductions);
        $this->assertEquals($expected['net'], (float) $slip->net_pay);
        $this->assertEquals(round(15312.50 - $expected['total'], 2), (float) $slip->net_pay);

        $this->assertEquals(15312.50, (float) $payroll->total_gross);
        $this->assertEquals($expected['total'], (float) $payroll->total_deductions);
        $this->assertEquals($expected['net'], (float) $payroll->total_net);
        $this->assertSame(0, LedgerEntry::count(), 'A draft must not touch the ledger.');
        $this->assertTrue(AuditLog::where(['action' => 'Draft created', 'table_name' => 'payrolls'])->exists());
    }

    public function test_only_employees_with_attendance_in_the_period_get_payslips(): void
    {
        $worked = $this->employee();
        $idle = $this->employee();
        $outside = $this->employee();
        $this->attend($worked, '2026-11-03', '2026-11-04');
        $this->attend($outside, '2026-10-01', '2026-10-02');

        $payroll = app(PayrollService::class)->run(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-15'), $this->treasurer->user_id);

        $this->assertSame([$worked->employee_id], $payroll->payslips->pluck('employee_id')->all());
        $this->assertNotContains($idle->employee_id, $payroll->payslips->pluck('employee_id')->all());
    }

    public function test_a_period_without_attendance_is_refused(): void
    {
        $this->actingAs($this->treasurer)->from('/payrolls/create')
            ->post('/payrolls', ['period_start' => '2026-11-01', 'period_end' => '2026-11-15'])
            ->assertSessionHasErrors('period_start');

        $this->assertSame(0, Payroll::count());
    }

    public function test_period_dates_are_validated(): void
    {
        $this->actingAs($this->treasurer);

        $this->post('/payrolls', [])->assertSessionHasErrors(['period_start', 'period_end']);
        $this->post('/payrolls', ['period_start' => '2026-11-10', 'period_end' => '2026-11-01'])->assertSessionHasErrors('period_end');
        $this->post('/payrolls', ['period_start' => '2026-11-10', 'period_end' => '2026-11-30'])->assertSessionHasErrors('period_end');   // in the future
        $this->post('/payrolls', ['period_start' => '2026-09-01', 'period_end' => '2026-11-01'])->assertSessionHasErrors('period_end');   // over 31 days
    }

    // ---- access ----------------------------------------------------------------------------

    public function test_only_the_treasurer_can_run_finalize_or_discard(): void
    {
        $draft = $this->draft();

        foreach ([User::PRESIDENT, User::EMPLOYEE, User::STORE_SUPERVISOR, User::INVENTORY_CLERK, 'Administrator', 'Pending'] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/payrolls/create')->assertForbidden();
            $this->actingAs($user)->post('/payrolls', ['period_start' => '2026-11-01', 'period_end' => '2026-11-15'])->assertForbidden();
            $this->actingAs($user)->post("/payrolls/{$draft->payroll_id}/finalize")->assertForbidden();
            $this->actingAs($user)->delete("/payrolls/{$draft->payroll_id}")->assertForbidden();
        }

        $this->assertSame('Draft', $draft->refresh()->status);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_president_and_treasurer_can_view_but_others_cannot(): void
    {
        $draft = $this->draft();

        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/payrolls')->assertOk();
            $this->actingAs($user)->get("/payrolls/{$draft->payroll_id}")->assertOk();
        }

        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR, User::INVENTORY_CLERK, 'Administrator', 'Pending'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/payrolls')->assertForbidden();
            $this->actingAs($user)->get("/payrolls/{$draft->payroll_id}")->assertForbidden();
        }
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/payrolls')->assertRedirect(route('staff.login'));
    }

    // ---- overlapping periods ---------------------------------------------------------------

    public function test_overlapping_periods_are_refused_but_neighbouring_periods_are_fine(): void
    {
        $existing = $this->draft('2026-11-01', '2026-11-15');
        $emp = $this->employee();
        $this->attend($emp, '2026-10-20', '2026-11-16');

        $overlaps = [
            ['2026-11-15', '2026-11-16'],   // shares the last day
            ['2026-10-25', '2026-11-01'],   // shares the first day
            ['2026-11-05', '2026-11-10'],   // inside
            ['2026-10-20', '2026-11-16'],   // surrounds
        ];
        foreach ($overlaps as [$s, $e]) {
            $this->actingAs($this->treasurer)->from('/payrolls/create')
                ->post('/payrolls', ['period_start' => $s, 'period_end' => $e])
                ->assertSessionHasErrors('period_start');
        }
        $this->assertSame(1, Payroll::count());

        $this->actingAs($this->treasurer)->post('/payrolls', ['period_start' => '2026-11-16', 'period_end' => '2026-11-16'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->treasurer)->post('/payrolls', ['period_start' => '2026-10-20', 'period_end' => '2026-10-31'])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, Payroll::count());
        $this->assertNotNull($existing);
    }

    public function test_a_finalized_period_also_blocks_overlap(): void
    {
        $draft = $this->draft();
        app(PayrollService::class)->finalize($draft, $this->treasurer->user_id);

        $this->actingAs($this->treasurer)->from('/payrolls/create')
            ->post('/payrolls', ['period_start' => '2026-11-10', 'period_end' => '2026-11-12'])
            ->assertSessionHasErrors('period_start');
    }

    // ---- finalizing ------------------------------------------------------------------------

    public function test_finalizing_locks_the_payroll_and_creates_a_ledger_expense(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->treasurer)->post("/payrolls/{$draft->payroll_id}/finalize")
            ->assertRedirect("/payrolls/{$draft->payroll_id}")->assertSessionHas('status');

        $draft->refresh();
        $this->assertSame('Final', $draft->status);

        $entry = LedgerEntry::firstOrFail();
        $this->assertSame('Expense', $entry->entry_type);
        $this->assertEquals(15312.50, (float) $entry->amount);
        $this->assertSame('Payroll', $entry->reference_type);
        $this->assertSame($draft->payroll_id, (int) $entry->reference_id);
        $this->assertSame($this->treasurer->user_id, $entry->recorded_by);
        $this->assertSame('2026-11-16', $entry->entry_date->toDateString());
        $this->assertStringContainsString('Nov 01, 2026', $entry->description);
        $this->assertTrue(AuditLog::where(['action' => 'Finalized', 'table_name' => 'payrolls', 'record_id' => $draft->payroll_id])->exists());
    }

    public function test_a_payroll_can_only_be_finalized_once(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->treasurer)->post("/payrolls/{$draft->payroll_id}/finalize");
        $this->actingAs($this->treasurer)->from('/payrolls')->post("/payrolls/{$draft->payroll_id}/finalize")
            ->assertSessionHasErrors('payroll');

        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_a_finalized_payroll_cannot_be_changed_or_deleted(): void
    {
        $draft = $this->draft();
        app(PayrollService::class)->finalize($draft, $this->treasurer->user_id);
        $slip = Payslip::firstOrFail();

        // Through the screens
        $this->actingAs($this->treasurer)->from('/payrolls')->delete("/payrolls/{$draft->payroll_id}")->assertSessionHasErrors('payroll');
        $this->assertTrue(Payroll::whereKey($draft->payroll_id)->exists());
        $this->assertSame(1, Payslip::count());

        // Through the models, from any code path
        $this->assertThrows(fn () => Payroll::find($draft->payroll_id)->update(['total_gross' => 1]), \LogicException::class);
        $this->assertThrows(fn () => Payroll::find($draft->payroll_id)->update(['status' => 'Draft']), \LogicException::class);
        $this->assertThrows(fn () => Payroll::find($draft->payroll_id)->delete(), \LogicException::class);
        $this->assertThrows(fn () => $slip->update(['net_pay' => 1]), \LogicException::class);
        $this->assertThrows(fn () => $slip->delete(), \LogicException::class);
        $this->assertThrows(fn () => app(PayrollService::class)->discard($draft, $this->treasurer->user_id), \Illuminate\Validation\ValidationException::class);

        $this->assertEquals(15312.50, (float) $draft->refresh()->total_gross);
    }

    // ---- discarding a draft ----------------------------------------------------------------

    public function test_a_draft_can_be_discarded_and_the_period_run_again(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->treasurer)->delete("/payrolls/{$draft->payroll_id}")->assertRedirect('/payrolls');

        $this->assertSame(0, Payroll::count());
        $this->assertSame(0, Payslip::count());
        $this->assertSame(0, LedgerEntry::count());

        $this->actingAs($this->treasurer)->post('/payrolls', ['period_start' => '2026-11-01', 'period_end' => '2026-11-15'])->assertSessionHasNoErrors();
        $this->assertSame(1, Payroll::count());
    }

    // ---- screens ---------------------------------------------------------------------------

    public function test_screens_show_money_in_peso_format_and_the_right_buttons(): void
    {
        $draft = $this->draft();

        $show = $this->actingAs($this->treasurer)->get("/payrolls/{$draft->payroll_id}")->assertOk();
        $show->assertSee('₱15,312.50')->assertSee('₱762.50')->assertSee('Finalize payroll')->assertSee('Delete draft');

        $this->actingAs($this->treasurer)->get('/payrolls')->assertSee('₱15,312.50');

        $president = $this->actingAs($this->staff(User::PRESIDENT))->get("/payrolls/{$draft->payroll_id}")->assertOk();
        $president->assertSee('₱15,312.50')->assertDontSee('Finalize payroll');

        app(PayrollService::class)->finalize($draft, $this->treasurer->user_id);
        $this->actingAs($this->treasurer)->get("/payrolls/{$draft->payroll_id}")
            ->assertDontSee('Finalize payroll')->assertDontSee('Delete draft')->assertSee('cannot be changed');
    }

    public function test_run_form_marks_required_fields_and_messages_use_the_alert(): void
    {
        $this->actingAs($this->treasurer)->get('/payrolls/create')->assertOk()
            ->assertSee('<span class="req">*</span> Required field', false)
            ->assertSee('Period start <span class="req">*</span>', false)
            ->assertSee('Period end <span class="req">*</span>', false);

        $this->draft();
        $this->actingAs($this->treasurer)->from('/payrolls/create')->followingRedirects()
            ->post('/payrolls', ['period_start' => '2026-11-05', 'period_end' => '2026-11-06'])
            ->assertSee('role="alert"', false)->assertSee('class="err"', false)->assertSee('overlaps payroll #');
    }
}
