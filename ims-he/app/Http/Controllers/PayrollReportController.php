<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\PdfService;

/** Payroll report for one period: totals of gross, each deduction and net, plus a line per employee (Task B5). */
class PayrollReportController extends Controller
{
    public function show(Payroll $payroll)
    {
        return view('payrolls.report', $this->data($payroll));
    }

    public function pdf(Payroll $payroll, PdfService $pdf)
    {
        return response($pdf->render('payrolls.report-pdf', $this->data($payroll)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="payroll-report-' . $payroll->period_start->toDateString() . '-to-' . $payroll->period_end->toDateString() . '.pdf"',
        ]);
    }

    private function data(Payroll $payroll): array
    {
        $payroll->load('processor.employee', 'payslips.employee');

        return [
            'payroll' => $payroll,
            'totals' => [
                'employees' => $payroll->payslips->count(),
                'gross' => $payroll->total_gross,
                'sss' => $payroll->payslips->sum('sss_deduction'),
                'philhealth' => $payroll->payslips->sum('philhealth_deduction'),
                'pagibig' => $payroll->payslips->sum('pagibig_deduction'),
                'tax' => $payroll->payslips->sum('tax_deduction'),
                'deductions' => $payroll->total_deductions,
                'net' => $payroll->total_net,
            ],
        ];
    }
}
