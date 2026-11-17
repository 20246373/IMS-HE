<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private LedgerService $ledger) {}

    public function run(CarbonInterface $start, CarbonInterface $end, User $by): Payroll
    {
        if ($end->lt($start)) {
            throw ValidationException::withMessages(['period' => 'The payroll end date must be on or after the start date.']);
        }

        return DB::transaction(function () use ($start, $end, $by): Payroll {
            $periodStart = $start->toDateString();
            $periodEnd = $end->toDateString();

            if (Payroll::query()
                ->where('period_start', '<=', $periodEnd)
                ->where('period_end', '>=', $periodStart)
                ->exists()) {
                throw ValidationException::withMessages(['period' => 'This period overlaps an existing payroll.']);
            }

            $payroll = Payroll::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'processed_by' => $by->user_id,
                'status' => 'Draft',
            ]);

            $totalGross = 0.0;
            $totalDeductions = 0.0;
            $totalNet = 0.0;

            foreach (Employee::where('status', 'Active')->orderBy('employee_id')->get() as $employee) {
                $attendance = Attendance::where('employee_id', $employee->employee_id)
                    ->whereBetween('work_date', [$periodStart, $periodEnd])
                    ->whereNotNull('time_out')
                    ->get();
                $values = $this->calculate($employee, $attendance);

                $payroll->payslips()->create(['employee_id' => $employee->employee_id] + $values);
                $totalGross += $values['gross_pay'];
                $totalDeductions += $values['total_deductions'];
                $totalNet += $values['net_pay'];
            }

            $payroll->update([
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
            ]);
            AuditLog::record('Created', 'payrolls', $payroll->payroll_id);

            return $payroll->load('payslips.employee');
        });
    }

    /**
     * @return array{days_worked:float,overtime_hours:float,gross_pay:float,sss_deduction:float,philhealth_deduction:float,pagibig_deduction:float,tax_deduction:float,other_deductions:float,total_deductions:float,net_pay:float}
     */
    public function calculate(Employee $employee, Collection $attendance): array
    {
        $config = config('payroll');
        $daysWorked = (float) $attendance->count();
        $overtimeHours = round((float) $attendance->sum('overtime_hours'), 2);
        $dailyRate = (float) $employee->daily_rate;
        $gross = round(
            $daysWorked * $dailyRate
            + $overtimeHours * ($dailyRate / (float) $config['hours_per_day']) * (float) $config['overtime_multiplier'],
            2,
        );
        $sss = $this->deduction($gross, $config['sss']);
        $philhealth = $this->deduction($gross, $config['philhealth']);
        $pagibig = $this->deduction($gross, $config['pagibig']);
        $tax = $this->withholdingTax($gross, $config['withholding_tax_brackets']);
        $totalDeductions = round($sss + $philhealth + $pagibig + $tax, 2);

        return [
            'days_worked' => $daysWorked,
            'overtime_hours' => $overtimeHours,
            'gross_pay' => $gross,
            'sss_deduction' => $sss,
            'philhealth_deduction' => $philhealth,
            'pagibig_deduction' => $pagibig,
            'tax_deduction' => $tax,
            'other_deductions' => 0.0,
            'total_deductions' => $totalDeductions,
            'net_pay' => round($gross - $totalDeductions, 2),
        ];
    }

    public function finalize(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll): Payroll {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->payroll_id);

            if ($payroll->status !== 'Draft') {
                throw ValidationException::withMessages(['payroll' => 'Only Draft payrolls can be finalized.']);
            }

            $payroll->update(['status' => 'Final']);
            $this->ledger->record(
                'Expense',
                (float) $payroll->total_net,
                "Payroll {$payroll->period_start->toDateString()} to {$payroll->period_end->toDateString()}",
                'Payroll',
                $payroll->payroll_id,
            );
            AuditLog::record('Finalized', 'payrolls', $payroll->payroll_id);

            return $payroll->fresh();
        });
    }

    public function deleteDraft(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll): void {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->payroll_id);

            if ($payroll->status !== 'Draft') {
                throw ValidationException::withMessages(['payroll' => 'Only Draft payrolls can be deleted.']);
            }

            $payroll->delete();
        });
    }

    /**
     * @param  array{mode:string,value:float}  $rule
     */
    private function deduction(float $gross, array $rule): float
    {
        if ($rule['mode'] === 'fixed') {
            return round($gross > 0 ? (float) $rule['value'] : 0.0, 2);
        }

        return round($gross * (float) $rule['value'], 2);
    }

    /**
     * @param  array<int,array{up_to:?float,base:float,rate:float}>  $brackets
     */
    private function withholdingTax(float $gross, array $brackets): float
    {
        foreach ($brackets as $bracket) {
            if ($bracket['up_to'] === null || $gross <= $bracket['up_to']) {
                return round((float) $bracket['base'] + $gross * (float) $bracket['rate'], 2);
            }
        }

        return 0.0;
    }
}
