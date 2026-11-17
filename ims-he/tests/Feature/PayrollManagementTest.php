<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'username' => "payroll-{$number}",
            'email' => "payroll-{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'first_name' => 'Paolo',
            'last_name' => 'Santos',
            'position' => 'Clerk',
            'daily_rate' => 600,
            'date_hired' => '2025-01-15',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_computation_uses_config_for_five_salary_examples(): void
    {
        $service = app(PayrollService::class);
        $employee = $this->employee();
        $attendance = collect();

        foreach ([
            ['days' => 0, 'overtime' => 0, 'gross' => 0.0],
            ['days' => 1, 'overtime' => 0, 'gross' => 600.0],
            ['days' => 5, 'overtime' => 2, 'gross' => 3187.50],
            ['days' => 10, 'overtime' => 4, 'gross' => 6375.0],
            ['days' => 20, 'overtime' => 12, 'gross' => 13125.00],
        ] as $example) {
            $rows = collect($example['days'] > 0 ? range(1, $example['days']) : [])
                ->map(fn (int $day): Attendance => new Attendance([
                    'overtime_hours' => $example['days'] > 0 ? $example['overtime'] / $example['days'] : 0,
                ]));
            $values = $service->calculate($employee, $rows);

            $this->assertSame($example['gross'], $values['gross_pay']);
            $this->assertSame(round($example['gross'] - $values['total_deductions'], 2), $values['net_pay']);
        }
    }

    public function test_run_creates_payslips_for_active_employees_only_and_skips_open_attendance(): void
    {
        $treasurer = $this->user(User::TREASURER);
        $active = $this->employee();
        $inactive = $this->employee(['first_name' => 'Inactive', 'status' => 'Resigned']);
        Attendance::create(['employee_id' => $active->employee_id, 'work_date' => '2026-11-01', 'time_in' => '08:00', 'time_out' => '16:00', 'overtime_hours' => 0]);
        Attendance::create(['employee_id' => $active->employee_id, 'work_date' => '2026-11-02', 'time_in' => '08:00', 'time_out' => null, 'overtime_hours' => 0]);

        $payroll = app(PayrollService::class)->run(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), $treasurer);

        $this->assertCount(1, $payroll->payslips);
        $this->assertSame($active->employee_id, $payroll->payslips->first()->employee_id);
        $this->assertSame(1.0, (float) $payroll->payslips->first()->days_worked);
        $this->assertNotSame($inactive->employee_id, $payroll->payslips->first()->employee_id);
    }

    public function test_overlapping_and_identical_payroll_periods_are_refused(): void
    {
        $treasurer = $this->user(User::TREASURER);
        $service = app(PayrollService::class);
        $service->run(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-15'), $treasurer);

        try {
            $service->run(Carbon::parse('2026-11-15'), Carbon::parse('2026-11-30'), $treasurer);
            $this->fail('An overlapping payroll period was created.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payrolls', 1);
        }

        $service->run(Carbon::parse('2026-12-01'), Carbon::parse('2026-12-15'), $treasurer);

        $this->expectException(ValidationException::class);
        $service->run(Carbon::parse('2026-12-01'), Carbon::parse('2026-12-15'), $treasurer);
    }

    public function test_finalize_creates_one_expense_and_final_payroll_cannot_change_or_delete(): void
    {
        $treasurer = $this->user(User::TREASURER);
        $employee = $this->employee();
        Attendance::create([
            'employee_id' => $employee->employee_id,
            'work_date' => '2026-11-01',
            'time_in' => '08:00',
            'time_out' => '16:00',
            'overtime_hours' => 0,
        ]);
        $payroll = app(PayrollService::class)->run(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-15'), $treasurer);
        $service = app(PayrollService::class);

        $service->finalize($payroll);

        $this->assertSame('Final', $payroll->fresh()->status);
        $this->assertDatabaseHas('ledger_entries', ['entry_type' => 'Expense', 'reference_type' => 'Payroll', 'reference_id' => $payroll->payroll_id]);
        $this->assertDatabaseCount('ledger_entries', 1);

        try {
            $service->finalize($payroll->fresh());
            $this->fail('A Final payroll was finalized.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ledger_entries', 1);
        }

        $this->expectException(ValidationException::class);
        $service->deleteDraft($payroll->fresh());
    }

    public function test_roles_allow_only_treasurer_to_run_and_finalize_while_president_can_view(): void
    {
        $president = $this->user(User::PRESIDENT);
        $employee = $this->user(User::EMPLOYEE);

        $this->actingAs($president)->get(route('payrolls.index'))->assertOk();
        $this->actingAs($president)->get(route('payrolls.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('payrolls.index'))->assertForbidden();
    }
}
