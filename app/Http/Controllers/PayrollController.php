<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index()
    {
        return view('payrolls.index', [
            'payrolls' => Payroll::with('processor')->orderByDesc('period_start')->paginate(20),
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
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);

        $payroll = $this->payroll->run($start, $end, auth()->id());

        return redirect()->route('payrolls.show', $payroll)->with('status', 'Payroll generated.');
    }

    public function show(Payroll $payroll)
    {
        $payroll->load('payslips.employee');

        return view('payrolls.show', ['payroll' => $payroll]);
    }

    public function finalize(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'Draft') {
            return back()->withErrors(['status' => 'Only draft payrolls can be finalized.']);
        }

        DB::transaction(function () use ($payroll) {
            $payroll->update(['status' => 'Finalized']);

            LedgerEntry::create([
                'entry_date' => today(),
                'entry_type' => 'expense',
                'amount' => $payroll->total_net,
                'description' => "Payroll #{$payroll->payroll_id} ({$payroll->period_start} to {$payroll->period_end})",
                'reference_type' => 'Payroll',
                'reference_id' => $payroll->payroll_id,
                'recorded_by' => auth()->id(),
            ]);
        });

        return back()->with('status', 'Payroll finalized and expense recorded.');
    }

    public function payslip(Payslip $payslip)
    {
        $payslip->load(['employee', 'payroll']);

        return view('payslips.show', ['payslip' => $payslip]);
    }
}
