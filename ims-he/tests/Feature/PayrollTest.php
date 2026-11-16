<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'username' => "payroll-user-{$n}",
            'email' => "payroll{$n}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function employee(float $rate = 800): Employee
    {
        return Employee::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz '.uniqid(),
            'position' => 'Cashier',
            'daily_rate' => $rate,
            'status' => 'Active',
        ]);
    }

    private function attendance(Employee $employee, string $date, string $in = '08:00', string $out = '17:00', float $ot = 0): Attendance
    {
        return Attendance::create([
            'employee_id' => $employee->employee_id,
            'work_date' => $date,
            'time_in' => $in,
            'time_out' => $out,
            'overtime_hours' => $ot,
        ]);
    }

    public function test_clock_in_and_clock_out_compute_overtime(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee();
        $this->actingAs($treasurer);

        $this->post('/attendance/clock-in', ['employee_id' => $employee->employee_id])
            ->assertSessionHasNoErrors();

        $record = Attendance::firstOrFail();
        $this->assertNull($record->time_out);
        $this->assertEquals(0.0, (float) $record->overtime_hours);

        // HTTP clock-out uses the current time; backdate time_in so it is always earlier.
        $record->update(['time_in' => '00:00:00']);
        $this->post("/attendance/{$record->attendance_id}/clock-out")
            ->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->time_out);

        // Overtime math is verified through the service with controlled times.
        $shift = Attendance::create([
            'employee_id' => $employee->employee_id,
            'work_date' => '2026-10-02',
            'time_in' => '08:00:00',
            'time_out' => null,
            'overtime_hours' => 0,
        ]);
        app(AttendanceService::class)->clockOut($shift, '17:30');

        $this->assertEquals(1.5, (float) $shift->fresh()->overtime_hours); // 9.5h - 8h
    }

    public function test_one_attendance_row_per_employee_per_date(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee();
        $this->actingAs($treasurer);

        $this->post('/attendance', [
            'employee_id' => $employee->employee_id,
            'work_date' => '2026-10-05',
            'time_in' => '08:00',
            'time_out' => '17:00',
        ])->assertSessionHasNoErrors();

        $this->post('/attendance', [
            'employee_id' => $employee->employee_id,
            'work_date' => '2026-10-05',
            'time_in' => '09:00',
            'time_out' => '18:00',
        ])->assertSessionHasErrors();

        $this->assertEquals(1, Attendance::count());
    }

    public function test_payroll_run_computes_payslips_with_all_deductions(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee(rate: 800);

        // 10 full days + one day with 2h overtime in the period.
        for ($d = 1; $d <= 10; $d++) {
            $this->attendance($employee, "2026-10-{$d}", '08:00', '17:00');
        }
        $this->attendance($employee, '2026-10-11', '08:00', '19:00', ot: 2);

        // Outside the period - must not count.
        $this->attendance($employee, '2026-09-30', '08:00', '17:00');

        $payroll = app(PayrollService::class)->run(
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            $treasurer->user_id,
        );

        $this->assertEquals('Draft', $payroll->status);
        $this->assertEquals(1, $payroll->payslips()->count());

        $payslip = $payroll->payslips()->firstOrFail();
        $this->assertEquals(11.0, (float) $payslip->days_worked);
        $this->assertEquals(2.0, (float) $payslip->overtime_hours);

        // gross = 11*800 + 2*(800/8)*1.25 = 8800 + 250 = 9050
        $this->assertEquals(9050.0, (float) $payslip->gross_pay);

        // Deductions from config/payroll.php rates (5% SSS, 2.5% PhilHealth,
        // 2% Pag-IBIG capped at 100, BIR brackets -> 0 tax at this income).
        $this->assertEquals(452.50, (float) $payslip->sss_deduction);
        $this->assertEquals(226.25, (float) $payslip->philhealth_deduction);
        $this->assertEquals(100.00, (float) $payslip->pagibig_deduction);
        $this->assertEquals(0.00, (float) $payslip->tax_deduction);
        $this->assertEquals(0.00, (float) $payslip->other_deductions);

        $expectedDeductions = 452.50 + 226.25 + 100.00;
        $this->assertEquals(round($expectedDeductions, 2), (float) $payslip->total_deductions);
        $this->assertEquals(round(9050 - $expectedDeductions, 2), (float) $payslip->net_pay);

        // Payroll totals equal the sum of payslips (SDD 7.2.10).
        $this->assertEquals((float) $payslip->gross_pay, (float) $payroll->total_gross);
        $this->assertEquals((float) $payslip->total_deductions, (float) $payroll->total_deductions);
        $this->assertEquals((float) $payslip->net_pay, (float) $payroll->total_net);
    }

    public function test_tax_deduction_uses_the_configured_brackets(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        // High earner so withholding tax applies: 20 days x 50000 = 1,000,000 gross.
        $employee = $this->employee(rate: 50000);
        $this->attendance($employee, '2026-10-01', '08:00', '17:00');

        $payroll = app(PayrollService::class)->run(
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            $treasurer->user_id,
        );

        $payslip = $payroll->payslips()->firstOrFail();
        // gross = 50,000 -> bracket over 33,333.33: 1,875 + 20% x (50,000-33,333.33)
        $expected = round(1875 + (50000 - 33333.33) * 0.20, 2);
        $this->assertEquals($expected, (float) $payslip->tax_deduction);
    }

    public function test_finalize_is_one_way_and_writes_the_expense_entry(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee(rate: 800);
        $this->attendance($employee, '2026-10-01', '08:00', '17:00');

        $payroll = app(PayrollService::class)->run(
            Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), $treasurer->user_id);

        $this->actingAs($treasurer)->post("/payroll/{$payroll->payroll_id}/finalize")
            ->assertSessionHasNoErrors();

        $this->assertEquals('Final', $payroll->fresh()->status);

        // One-way: a second finalize is rejected and writes no second ledger entry.
        $this->actingAs($treasurer)->post("/payroll/{$payroll->payroll_id}/finalize")
            ->assertSessionHasErrors();

        $this->assertEquals(1, LedgerEntry::where('reference_type', 'Payroll')->count());
    }

    public function test_other_deductions_can_be_set_while_draft_and_blocked_after_final(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee(rate: 800);
        $this->attendance($employee, '2026-10-01', '08:00', '17:00');

        $payroll = app(PayrollService::class)->run(
            Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), $treasurer->user_id);
        $payslip = $payroll->payslips()->firstOrFail();

        $this->actingAs($treasurer)->patch("/payroll/payslips/{$payslip->payslip_id}", [
            'other_deductions' => 50,
        ])->assertSessionHasNoErrors();

        $payslip->refresh();
        $this->assertEquals(50.0, (float) $payslip->other_deductions);
        // net = gross - (sss + ph + pagibig + tax + other)
        $this->assertEquals(
            round((float) $payslip->gross_pay - (float) $payslip->total_deductions, 2),
            (float) $payslip->net_pay
        );
        // Payroll totals were refreshed from the payslips.
        $this->assertEquals((float) $payslip->net_pay, (float) $payroll->fresh()->total_net);

        app(PayrollService::class)->finalize($payroll, $treasurer->user_id);

        $this->actingAs($treasurer)->patch("/payroll/payslips/{$payslip->payslip_id}", [
            'other_deductions' => 60,
        ])->assertSessionHasErrors();
    }

    public function test_payroll_computation_is_restricted_to_the_treasurer(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->staff(User::PRESIDENT))->get('/payroll/create')->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT))->post('/payroll', [
            'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
        ])->assertForbidden();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->post('/payroll', [
            'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
        ])->assertForbidden();

        // President and Treasurer can still view batches and payslips.
        $payroll = Payroll::create([
            'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'processed_by' => $this->staff(User::TREASURER)->user_id, 'status' => 'Draft',
        ]);
        $payslip = Payslip::create([
            'payroll_id' => $payroll->payroll_id, 'employee_id' => $employee->employee_id,
            'days_worked' => 1, 'gross_pay' => 800, 'total_deductions' => 0, 'net_pay' => 800,
        ]);

        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/payroll')->assertOk();
            $this->actingAs($user)->get("/payroll/{$payroll->payroll_id}")->assertOk();
            $this->actingAs($user)->get("/payroll/{$payroll->payroll_id}/payslips/{$payslip->payslip_id}")->assertOk();
        }

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->get('/payroll')->assertForbidden();
        $this->actingAs($this->staff(User::EMPLOYEE))->get('/payroll')->assertForbidden();
    }

    public function test_weekly_command_creates_a_draft_for_last_week_once(): void
    {
        $this->staff(User::TREASURER);
        $employee = $this->employee(rate: 800);

        // Last week Monday..Sunday work days.
        $monday = now()->subWeek()->startOfWeek();
        $this->attendance($employee, $monday->toDateString(), '08:00', '17:00');
        $this->attendance($employee, $monday->copy()->addDay()->toDateString(), '08:00', '17:00');

        $this->artisan('payroll:compute-weekly')->assertSuccessful();

        $payroll = Payroll::firstOrFail();
        $this->assertEquals('Draft', $payroll->status);
        $this->assertEquals($monday->toDateString(), $payroll->period_start->toDateString());
        $this->assertEquals($monday->copy()->endOfWeek()->toDateString(), $payroll->period_end->toDateString());
        $this->assertEquals(1, $payroll->payslips()->count());
        $this->assertEquals(1600.0, (float) $payroll->total_gross);

        // Idempotent: running again does not create a second batch.
        $this->artisan('payroll:compute-weekly')->assertSuccessful();
        $this->assertEquals(1, Payroll::count());
    }

    public function test_weekly_command_skips_when_no_treasurer_exists(): void
    {
        $this->artisan('payroll:compute-weekly')->assertFailed();
        $this->assertEquals(0, Payroll::count());
    }

    public function test_attendance_report_and_payslip_pdf(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $president = $this->staff(User::PRESIDENT);
        $employee = $this->employee(rate: 800);
        $this->attendance($employee, '2026-10-05', '08:00', '19:00', ot: 3);

        foreach ([$treasurer, $president] as $user) {
            $this->actingAs($user)->get('/reports/attendance?start_date=2026-10-01&end_date=2026-10-31')
                ->assertOk()->assertSee($employee->full_name)->assertSee('3.00');

            $response = $this->actingAs($user)
                ->get('/reports/attendance/download?start_date=2026-10-01&end_date=2026-10-31');
            $response->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        }

        $payroll = Payroll::create([
            'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'processed_by' => $treasurer->user_id, 'status' => 'Final',
        ]);
        $payslip = Payslip::create([
            'payroll_id' => $payroll->payroll_id, 'employee_id' => $employee->employee_id,
            'days_worked' => 1, 'overtime_hours' => 3, 'gross_pay' => 1100,
            'total_deductions' => 100, 'net_pay' => 1000,
        ]);

        $this->actingAs($treasurer)
            ->get("/payroll/{$payroll->payroll_id}/payslips/{$payslip->payslip_id}/pdf")
            ->assertOk();
    }

    public function test_payroll_report_download_works_for_treasurer_and_president(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $payroll = Payroll::create([
            'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'processed_by' => $treasurer->user_id, 'status' => 'Draft',
        ]);

        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $response = $this->actingAs($this->staff($role))
                ->get("/reports/payroll/download?payroll_id={$payroll->payroll_id}");
            $response->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        }

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get("/reports/payroll?payroll_id={$payroll->payroll_id}")->assertForbidden();
    }
}
