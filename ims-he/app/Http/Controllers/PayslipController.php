<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Services\PdfService;
use Illuminate\Support\Str;

/** Printable payslip and its PDF for one employee (Task B5). President and Treasurer only. */
class PayslipController extends Controller
{
    public function show(Payslip $payslip)
    {
        return view('payslips.show', ['payslip' => $this->load($payslip)]);
    }

    public function pdf(Payslip $payslip, PdfService $pdf)
    {
        $payslip = $this->load($payslip);
        $name = Str::slug($payslip->employee->full_name . ' ' . $payslip->payroll->period_start->toDateString() . ' ' . $payslip->payroll->period_end->toDateString());

        return response($pdf->render('payslips.pdf', ['payslip' => $payslip]), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="payslip-' . $name . '.pdf"',
        ]);
    }

    private function load(Payslip $payslip): Payslip
    {
        return $payslip->load('employee', 'payroll');
    }
}
