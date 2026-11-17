<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(): View
    {
        return view('payroll.index', [
            'payrolls' => Payroll::with('processor')->orderByDesc('period_end')->orderByDesc('payroll_id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('payroll.create', [
            'startDate' => now()->startOfMonth()->toDateString(),
            'endDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, PayrollService $payrolls): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $payroll = $payrolls->run(Carbon::parse($data['start_date']), Carbon::parse($data['end_date']), $request->user());

        return redirect()->route('payrolls.show', $payroll)->with('status', 'Draft payroll created.');
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load('payslips.employee', 'processor');

        return view('payroll.show', compact('payroll'));
    }

    public function payslip(Payroll $payroll, Payslip $payslip): View
    {
        if ($payslip->payroll_id !== $payroll->payroll_id) {
            abort(404);
        }

        return view('payroll.payslip', compact('payroll', 'payslip'));
    }

    public function download(Payroll $payroll): Response
    {
        $payroll->load('payslips.employee', 'processor');

        return Pdf::loadView('payroll.pdf', compact('payroll'))
            ->download("payroll-{$payroll->period_start->toDateString()}-to-{$payroll->period_end->toDateString()}.pdf");
    }

    public function finalize(Payroll $payroll, PayrollService $payrolls): RedirectResponse
    {
        $payrolls->finalize($payroll);

        return back()->with('status', 'Payroll finalized.');
    }

    public function destroy(Payroll $payroll, PayrollService $payrolls): RedirectResponse
    {
        $payrolls->deleteDraft($payroll);

        return redirect()->route('payrolls.index')->with('status', 'Draft payroll deleted.');
    }
}
