<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Financial Recording module UI (SDD 4.1, 7.2.18): ledger list, filters,
// manual entries, balance and end-of-day reconciliation.
class LedgerController extends Controller
{
    public function __construct(private FinancialService $financial) {}

    public function index(Request $request)
    {
        $filters = $this->validateFilters($request) + [
            'start_date' => null, 'end_date' => null, 'entry_type' => null,
        ];

        $entries = LedgerEntry::with('recorder')
            ->when($filters['entry_type'], fn ($q, $type) => $q->where('entry_type', $type))
            ->when($filters['start_date'], fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
            ->when($filters['end_date'], fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
            ->orderByDesc('entry_date')
            ->orderByDesc('ledger_id')
            ->paginate(25)
            ->withQueryString();

        $totals = LedgerEntry::query()
            ->when($filters['entry_type'], fn ($q, $type) => $q->where('entry_type', $type))
            ->when($filters['start_date'], fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
            ->when($filters['end_date'], fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'Income' THEN amount ELSE 0 END),0) as income")
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'Expense' THEN amount ELSE 0 END),0) as expenses")
            ->first();

        return view('ledger.index', [
            'entries' => $entries,
            'filters' => $filters,
            'income' => (float) $totals->income,
            'expenses' => (float) $totals->expenses,
            'balance' => round((float) $totals->income - (float) $totals->expenses, 2),
            'cashFlow' => $filters['start_date'] && $filters['end_date']
                ? $this->financial->cashFlow($filters['start_date'], $filters['end_date'])
                : null,
        ]);
    }

    public function create()
    {
        return view('ledger.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entry_type' => ['required', Rule::in([FinancialService::INCOME, FinancialService::EXPENSE])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $entry = $this->financial->recordManual(
            $data['entry_type'],
            (float) $data['amount'],
            $data['description'],
            $request->user()->user_id,
        );

        if (! empty($data['entry_date']) && $data['entry_date'] !== today()->toDateString()) {
            $entry->update(['entry_date' => $data['entry_date']]);
        }

        return redirect()->route('ledger.index')->with('status', 'Ledger entry recorded.');
    }

    public function reconcile(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $date = $data['date'] ?? today()->toDateString();

        return view('ledger.reconcile', [
            'date' => $date,
            'result' => $this->financial->reconcile($date),
        ]);
    }

    /** @return array{start_date:?string, end_date:?string, entry_type:?string} */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'entry_type' => ['nullable', Rule::in([FinancialService::INCOME, FinancialService::EXPENSE])],
        ]);
    }
}
