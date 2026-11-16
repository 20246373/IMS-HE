<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Payroll Processing module (SDD 4.1, 7.2.10-7.2.11; SRS UC06).
// Viewing: President + Treasurer. Computing/finalizing/editing: Treasurer only (SDD Section 5).
class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index()
    {
        return view('payroll.index', [
            'payrolls' => Payroll::with('processor')->orderByDesc('period_start')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('payroll.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after:period_start'],
        ]);

        $payroll = $this->payroll->run(
            Carbon::parse($data['period_start']),
            Carbon::parse($data['period_end']),
            $request->user()->user_id,
        );

        return redirect()->route('payroll.show', $payroll->payroll_id)
            ->with('status', "Payroll #{$payroll->payroll_id} computed as Draft.");
    }

    public function show(Payroll $payroll)
    {
        return view('payroll.show', [
            'payroll' => $payroll->load('processor', 'payslips.employee'),
        ]);
    }

    public function finalize(Request $request, Payroll $payroll)
    {
        $this->payroll->finalize($payroll, $request->user()->user_id);

        return back()->with('status', "Payroll #{$payroll->payroll_id} finalized.");
    }

    public function payslip(Payroll $payroll, Payslip $payslip)
    {
        return view('payroll.payslip', [
            'payslip' => $payslip->load('employee', 'payroll.processor'),
        ]);
    }

    public function payslipPdf(Payroll $payroll, Payslip $payslip)
    {
        return Pdf::loadView('payroll.pdf.payslip', [
            'payslip' => $payslip->load('employee', 'payroll.processor'),
        ])->setPaper('a5')->download("payslip-{$payslip->payslip_id}.pdf");
    }

    /** Manual other_deductions on a Draft payslip; recomputes totals (SDD 7.2.11). */
    public function updatePayslip(Request $request, Payslip $payslip)
    {
        $data = $request->validate([
            'other_deductions' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->payroll->updateOtherDeductions($payslip, (float) $data['other_deductions'], $request->user()->user_id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Payslip updated.');
    }
}
