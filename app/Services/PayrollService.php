<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /** Builds a Draft payroll batch with one payslip per active employee, from attendance in the period. */
    public function run(CarbonInterface $start, CarbonInterface $end, ?int $userId): Payroll
    {
        $cfg = config('payroll');

        return DB::transaction(function () use ($start, $end, $userId, $cfg) {
            $payroll = Payroll::create([
                'period_start' => $start->toDateString(),
                'period_end'   => $end->toDateString(),
                'processed_by' => $userId,
                'status'       => 'Draft',
            ]);

            $sumGross = $sumDed = $sumNet = 0.0;

            foreach (Employee::where('status', 'Active')->get() as $emp) {
                $att = $emp->attendances()->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])->get();

                $days = $att->where('status', 'Present')->count() + 0.5 * $att->where('status', 'Half-day')->count();
                $ot   = (float) $att->sum('overtime_hours');

                $rate   = (float) $emp->daily_rate;
                $hourly = $rate / $cfg['hours_per_day'];
                $gross  = round($days * $rate + $ot * $hourly * $cfg['overtime_multiplier'], 2);

                $sss  = round($gross * $cfg['sss_rate'], 2);
                $ph   = round($gross * $cfg['philhealth_rate'], 2);
                $pag  = $gross > 0 ? (float) $cfg['pagibig_fixed'] : 0.0;
                $tax  = round($gross * $cfg['tax_rate'], 2);
                $ded  = round($sss + $ph + $pag + $tax, 2);
                $net  = round($gross - $ded, 2);

                $payroll->payslips()->create([
                    'employee_id'          => $emp->id,
                    'days_worked'          => $days,
                    'overtime_hours'       => $ot,
                    'gross_pay'            => $gross,
                    'sss_deduction'        => $sss,
                    'philhealth_deduction' => $ph,
                    'pagibig_deduction'    => $pag,
                    'tax_deduction'        => $tax,
                    'total_deductions'     => $ded,
                    'net_pay'              => $net,
                ]);

                $sumGross += $gross; $sumDed += $ded; $sumNet += $net;
            }

            $payroll->update([
                'total_gross'      => round($sumGross, 2),
                'total_deductions' => round($sumDed, 2),
                'total_net'        => round($sumNet, 2),
            ]);

            return $payroll->load('payslips.employee');
        });
    }
}
