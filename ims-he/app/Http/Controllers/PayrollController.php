<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\PayrollService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Payroll run (Task B3). Treasurer runs, finalizes and discards drafts; President and Treasurer can view.
 * The arithmetic lives in PayrollService; a finalized payroll is locked there and in the models.
 */
class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index()
    {
        return view('payrolls.index', [
            'payrolls' => Payroll::with('processor.employee')->withCount('payslips')
                ->orderByDesc('period_start')->orderByDesc('payroll_id')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('payrolls.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start|before_or_equal:today',
        ]);

        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);

        if ($start->diffInDays($end) > 30) {
            throw ValidationException::withMessages(['period_end' => 'A payroll period can be at most 31 days.']);
        }

        $payroll = $this->payroll->run($start, $end, $request->user()->user_id);

        return redirect()->route('payrolls.show', $payroll->payroll_id)
            ->with('status', "Draft payroll #{$payroll->payroll_id} created. Review it, then finalize.");
    }

    public function show(Payroll $payroll)
    {
        return view('payrolls.show', [
            'payroll' => $payroll->load('processor.employee', 'payslips.employee'),
        ]);
    }

    public function finalize(Request $request, Payroll $payroll)
    {
        $payroll = $this->payroll->finalize($payroll, $request->user()->user_id);

        return redirect()->route('payrolls.show', $payroll->payroll_id)
            ->with('status', "Payroll #{$payroll->payroll_id} finalized. " . Money::peso($payroll->total_gross) . ' recorded as an expense in the ledger.');
    }

    /** Deletes a Draft so the period can be run again. */
    public function destroy(Request $request, Payroll $payroll)
    {
        $id = $payroll->payroll_id;
        $this->payroll->discard($payroll, $request->user()->user_id);

        return redirect()->route('payrolls.index')->with('status', "Draft payroll #{$id} deleted.");
    }
}
