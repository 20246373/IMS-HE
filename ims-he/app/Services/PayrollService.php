<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Payroll Processing module (SDD 4.1, 7.2.9-7.2.11; SRS UC06/UC17/UC18).
 *
 * Rates come from config/payroll.php (versioned statutory tables - see the
 * file header; they must be verified against current official tables).
 *
 * gross_pay = days_worked x daily_rate + overtime pay
 * overtime pay = overtime_hours x (daily_rate / hours_per_day) x overtime_multiplier
 * net_pay   = gross_pay - total_deductions
 *
 * A payroll batch is created as Draft; Draft -> Final is one-way
 * (finalize() refuses anything else) and writes the Expense ledger entry.
 */
class PayrollService
{
    public function __construct(private FinancialService $financial) {}

    /** Builds a Draft payroll batch with one payslip per active employee, from attendance in the period. */
    public function run(CarbonInterface $start, CarbonInterface $end, ?int $userId): Payroll
    {
        return DB::transaction(function () use ($start, $end, $userId) {
            $payroll = Payroll::create([
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'processed_by' => $userId,
                'status' => Payroll::STATUS_DRAFT,
            ]);

            $sumGross = $sumDed = $sumNet = 0.0;

            foreach (Employee::where('status', 'Active')->get() as $employee) {
                $attendance = $employee->attendance()
                    ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                    ->get();

                $days = (float) $attendance->count();
                $overtime = round((float) $attendance->sum('overtime_hours'), 2);

                $calculation = $this->calculate($employee, $days, $overtime);

                $payroll->payslips()->create($calculation + [
                    'employee_id' => $employee->employee_id,
                ]);

                $sumGross += $calculation['gross_pay'];
                $sumDed += $calculation['total_deductions'];
                $sumNet += $calculation['net_pay'];
            }

            $payroll->update([
                'total_gross' => round($sumGross, 2),
                'total_deductions' => round($sumDed, 2),
                'total_net' => round($sumNet, 2),
            ]);

            return $payroll->load('payslips.employee');
        });
    }

    /**
     * Draft -> Final is one-way. Writes the payroll Expense ledger entry inside
     * the same transaction so pay and ledger can never disagree.
     */
    public function finalize(Payroll $payroll, ?int $userId = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $userId) {
            $payroll = Payroll::whereKey($payroll->payroll_id)->lockForUpdate()->firstOrFail();

            if ($payroll->status !== Payroll::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll #{$payroll->payroll_id} is already {$payroll->status}.",
                ]);
            }

            $payroll->update(['status' => Payroll::STATUS_FINAL]);

            $this->financial->recordExpense(
                'Payroll',
                (int) $payroll->payroll_id,
                (float) $payroll->total_net,
                "Payroll {$payroll->period_start->toDateString()} to {$payroll->period_end->toDateString()}",
                $userId,
            );

            return $payroll;
        });
    }

    /**
     * Recomputes a Draft payslip after a manual other_deductions change so the
     * payroll totals always equal the sum of their payslips (SDD 7.2.10-11).
     */
    public function updateOtherDeductions(Payslip $payslip, float $otherDeductions, ?int $userId = null): Payslip
    {
        return DB::transaction(function () use ($payslip, $otherDeductions) {
            $payslip = Payslip::whereKey($payslip->payslip_id)->lockForUpdate()->firstOrFail();
            $payroll = Payroll::whereKey($payslip->payroll_id)->lockForUpdate()->firstOrFail();

            if ($payroll->status !== Payroll::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'payslip' => 'Payslips can only be edited while the payroll is a Draft.',
                ]);
            }

            $employee = $payslip->employee;
            $recomputed = $this->calculate($employee, (float) $payslip->days_worked, (float) $payslip->overtime_hours, $otherDeductions);

            $payslip->update($recomputed);
            $this->refreshPayrollTotals($payroll);

            return $payslip->fresh();
        });
    }

    /**
     * @return array<string, float|int> payslip column values (without employee_id)
     */
    private function calculate(Employee $employee, float $days, float $overtime, float $otherDeductions = 0.0): array
    {
        $cfg = config('payroll');

        $dailyRate = (float) $employee->daily_rate;
        $hourly = $dailyRate / max(1, (int) $cfg['hours_per_day']);

        $gross = round($days * $dailyRate + $overtime * $hourly * (float) $cfg['overtime_multiplier'], 2);

        $sss = round($gross * (float) $cfg['sss_rate'], 2);
        $philhealth = round($gross * (float) $cfg['philhealth_rate'], 2);
        $pagibig = $gross > 0 ? min(round($gross * (float) $cfg['pagibig_rate'], 2), (float) $cfg['pagibig_max']) : 0.0;
        $tax = $this->withholdingTax($gross, $cfg['tax_brackets']);
        $other = round($otherDeductions, 2);

        $totalDeductions = round($sss + $philhealth + $pagibig + $tax + $other, 2);

        return [
            'days_worked' => $days,
            'overtime_hours' => $overtime,
            'gross_pay' => $gross,
            'sss_deduction' => $sss,
            'philhealth_deduction' => $philhealth,
            'pagibig_deduction' => $pagibig,
            'tax_deduction' => $tax,
            'other_deductions' => $other,
            'total_deductions' => $totalDeductions,
            'net_pay' => round($gross - $totalDeductions, 2),
        ];
    }

    /** BIR-style bracket tax: base + rate x (gross - threshold). */
    private function withholdingTax(float $gross, array $brackets): float
    {
        $applicable = $brackets[0];
        foreach ($brackets as $bracket) {
            if ($gross > (float) $bracket['over']) {
                $applicable = $bracket;
            }
        }

        return round((float) $applicable['base'] + max(0, $gross - (float) $applicable['over']) * (float) $applicable['rate'], 2);
    }

    private function refreshPayrollTotals(Payroll $payroll): void
    {
        $totals = $payroll->payslips()
            ->selectRaw('COALESCE(SUM(gross_pay),0) as g, COALESCE(SUM(total_deductions),0) as d, COALESCE(SUM(net_pay),0) as n')
            ->first();

        $payroll->update([
            'total_gross' => round((float) $totals->g, 2),
            'total_deductions' => round((float) $totals->d, 2),
            'total_net' => round((float) $totals->n, 2),
        ]);
    }
}
