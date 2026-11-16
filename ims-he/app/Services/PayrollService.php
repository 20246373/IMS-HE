<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Payroll computation (SDD 8.1). Every rate and bracket comes from config/payroll.php.
 *
 *   run()       builds a Draft payroll: one payslip per employee who has attendance in the period.
 *   finalize()  locks a Draft (status Final) and records the payroll expense in the ledger.
 *   discard()   deletes a Draft so its period can be run again. A Final payroll can never be changed.
 *
 * All three run inside DB::transaction and throw ValidationException (shown by the shared alert).
 */
class PayrollService
{
    /** Gross pay = days worked x daily rate + overtime hours x hourly rate x overtime multiplier. */
    public function computeGross(float $dailyRate, float $daysWorked, float $overtimeHours): float
    {
        $cfg = config('payroll');
        $hourly = $dailyRate / $cfg['hours_per_day'];

        return round($daysWorked * $dailyRate + $overtimeHours * $hourly * $cfg['overtime_multiplier'], 2);
    }

    /**
     * Employee-share deductions for ONE payroll run.
     * Monthly tables (SSS, PhilHealth, Pag-IBIG) are applied to the monthly equivalent of the run's
     * gross (gross x periods_per_month) and the result is divided back to one run.
     *
     * @return array{sss: float, philhealth: float, pagibig: float, tax: float, total: float, net: float}
     */
    public function computeDeductions(float $gross): array
    {
        $cfg = config('payroll');
        $perMonth = max(1, (int) $cfg['periods_per_month']);

        if ($gross <= 0) {
            return ['sss' => 0.0, 'philhealth' => 0.0, 'pagibig' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'net' => 0.0];
        }

        $monthly = $gross * $perMonth;

        // SSS: round monthly pay to the nearest MSC step, clamp to [min, max], take the employee rate.
        $s = $cfg['sss'];
        $msc = floor(($monthly + $s['msc_step'] / 2) / $s['msc_step']) * $s['msc_step'];
        $msc = min(max($msc, $s['min_msc']), $s['max_msc']);
        $sss = round($msc * $s['employee_rate'] / $perMonth, 2);

        // PhilHealth: pay clamped to [floor, ceiling] x employee half of the premium.
        $p = $cfg['philhealth'];
        $phBase = min(max($monthly, $p['income_floor']), $p['income_ceiling']);
        $philhealth = round($phBase * $p['employee_rate'] / $perMonth, 2);

        // Pag-IBIG: 1% at or below the low-income limit, otherwise 2% of pay capped at the fund salary.
        $g = $cfg['pagibig'];
        $pagibigMonthly = $monthly <= $g['low_income_limit']
            ? $monthly * $g['low_income_rate']
            : min($monthly, $g['max_fund_salary']) * $g['employee_rate'];
        $pagibig = round($pagibigMonthly / $perMonth, 2);

        // Withholding tax: contributions are tax-exempt; annualize the rest, apply TRAIN, divide back.
        $taxable = max(0.0, $gross - $sss - $philhealth - $pagibig);
        $tax = round($this->annualTax($taxable * $perMonth * 12) / (12 * $perMonth), 2);

        $total = round($sss + $philhealth + $pagibig + $tax, 2);

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'tax' => $tax,
            'total' => $total,
            'net' => round($gross - $total, 2),
        ];
    }

    /** Builds a Draft payroll with one payslip per employee who has attendance in the period. */
    public function run(CarbonInterface $start, CarbonInterface $end, int $userId): Payroll
    {
        return DB::transaction(function () use ($start, $end, $userId) {
            $from = $start->toDateString();
            $to = $end->toDateString();

            $this->assertNoOverlap($from, $to);

            $employees = Employee::whereHas('attendance', fn ($q) => $q->whereBetween('work_date', [$from, $to]))
                ->orderBy('last_name')->orderBy('first_name')->get();

            if ($employees->isEmpty()) {
                throw ValidationException::withMessages(['period_start' => 'There are no attendance records in this period.']);
            }

            $payroll = Payroll::create([
                'period_start' => $from,
                'period_end' => $to,
                'processed_by' => $userId,
                'status' => Payroll::STATUS_DRAFT,
            ]);

            $sumGross = $sumDed = $sumNet = 0.0;

            foreach ($employees as $emp) {
                $records = $emp->attendance()->whereBetween('work_date', [$from, $to])->get();

                $days = (float) $records->count();            // one attendance record = one day worked
                $overtime = round((float) $records->sum('overtime_hours'), 2);

                $gross = $this->computeGross((float) $emp->daily_rate, $days, $overtime);
                $d = $this->computeDeductions($gross);

                $payroll->payslips()->create([
                    'employee_id' => $emp->employee_id,
                    'days_worked' => $days,
                    'overtime_hours' => $overtime,
                    'gross_pay' => $gross,
                    'sss_deduction' => $d['sss'],
                    'philhealth_deduction' => $d['philhealth'],
                    'pagibig_deduction' => $d['pagibig'],
                    'tax_deduction' => $d['tax'],
                    'total_deductions' => $d['total'],
                    'net_pay' => $d['net'],
                ]);

                $sumGross += $gross;
                $sumDed += $d['total'];
                $sumNet += $d['net'];
            }

            $payroll->update([
                'total_gross' => round($sumGross, 2),
                'total_deductions' => round($sumDed, 2),
                'total_net' => round($sumNet, 2),
            ]);

            AuditLog::record('Draft created', 'payrolls', $payroll->payroll_id, $userId);

            return $payroll->refresh()->load('payslips.employee');
        });
    }

    /** Locks a Draft and records the payroll expense in the ledger. Cannot be undone. */
    public function finalize(Payroll $payroll, int $userId): Payroll
    {
        return DB::transaction(function () use ($payroll, $userId) {
            $payroll = Payroll::lockForUpdate()->findOrFail($payroll->payroll_id);

            if (! $payroll->isDraft()) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll #{$payroll->payroll_id} is already finalized and cannot be changed.",
                ]);
            }

            $payroll->update(['status' => Payroll::STATUS_FINAL]);

            // LEDGER HOOK (Sales and Financial Recording, Reynier): the payroll expense is the gross pay
            // of the run. If the ledger module later gets its own service, call it here instead.
            LedgerEntry::create([
                'entry_date' => today()->toDateString(),
                'entry_type' => 'Expense',
                'amount' => $payroll->total_gross,
                'description' => sprintf(
                    'Payroll %s to %s (Payroll #%d)',
                    $payroll->period_start->format('M d, Y'),
                    $payroll->period_end->format('M d, Y'),
                    $payroll->payroll_id,
                ),
                'reference_type' => 'Payroll',
                'reference_id' => $payroll->payroll_id,
                'recorded_by' => $userId,
            ]);

            AuditLog::record('Finalized', 'payrolls', $payroll->payroll_id, $userId);

            return $payroll;
        });
    }

    /** Deletes a Draft (and its payslips) so the period can be run again. */
    public function discard(Payroll $payroll, int $userId): void
    {
        DB::transaction(function () use ($payroll, $userId) {
            $payroll = Payroll::lockForUpdate()->findOrFail($payroll->payroll_id);

            if (! $payroll->isDraft()) {
                throw ValidationException::withMessages([
                    'payroll' => "Payroll #{$payroll->payroll_id} is finalized and cannot be deleted.",
                ]);
            }

            $id = $payroll->payroll_id;
            $payroll->payslips()->delete();
            $payroll->delete();

            AuditLog::record('Discarded', 'payrolls', $id, $userId);
        });
    }

    /** Annual TRAIN tax: base + rate x (income - bracket start) for the highest bracket the income passes. */
    private function annualTax(float $annualTaxable): float
    {
        $row = null;
        foreach (config('payroll.tax.annual_brackets') as $bracket) {
            if ($annualTaxable > $bracket['over']) {
                $row = $bracket;
            }
        }

        return $row ? $row['base'] + $row['rate'] * ($annualTaxable - $row['over']) : 0.0;
    }

    private function assertNoOverlap(string $from, string $to): void
    {
        $clash = Payroll::where('period_start', '<=', $to)->where('period_end', '>=', $from)->first();

        if ($clash) {
            throw ValidationException::withMessages([
                'period_start' => sprintf(
                    'This period overlaps payroll #%d (%s to %s, %s).',
                    $clash->payroll_id,
                    $clash->period_start->format('M d, Y'),
                    $clash->period_end->format('M d, Y'),
                    $clash->status,
                ),
            ]);
        }
    }
}
