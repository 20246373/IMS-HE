<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Services\LedgerService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validateFilters($request);
        $query = $this->filteredQuery($filters);

        $totals = [
            'Income' => (clone $query)->where('entry_type', 'Income')->sum('amount'),
            'Expense' => (clone $query)->where('entry_type', 'Expense')->sum('amount'),
        ];
        $totals['Net'] = $totals['Income'] - $totals['Expense'];

        $entries = $query->with('recorder')
            ->orderByDesc('entry_date')
            ->orderByDesc('ledger_id')
            ->paginate(20)
            ->withQueryString();

        return view('ledger.index', [
            'entries' => $entries,
            'totals' => $totals,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'type' => $filters['entry_type'] ?? null,
        ]);
    }

    public function create(): View
    {
        return view('ledger.create');
    }

    public function store(Request $request, LedgerService $ledger): RedirectResponse
    {
        $request->merge(['amount' => Money::parse($request->input('amount'))]);
        $data = $request->validate([
            'entry_type' => ['required', Rule::in(['Income', 'Expense'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $ledger->record(
            $data['entry_type'],
            (float) $data['amount'],
            $data['description'],
        );

        return redirect()->route('ledger.index')->with('status', 'Ledger entry recorded.');
    }

    public function reverse(LedgerEntry $ledger_entry, LedgerService $ledger): RedirectResponse
    {
        $error = null;
        DB::transaction(function () use ($ledger_entry, $ledger, &$error): void {
            $ledger_entry = LedgerEntry::query()->lockForUpdate()->findOrFail($ledger_entry->ledger_id);

            if (str_starts_with((string) $ledger_entry->description, 'Reversal of ledger #')) {
                $error = 'A reversal cannot be reversed.';

                return;
            }

            $reversalDescription = "Reversal of ledger #{$ledger_entry->ledger_id}";

            if (LedgerEntry::query()->where('description', $reversalDescription)->exists()) {
                $error = 'This ledger entry has already been reversed.';

                return;
            }

            $ledger->record(
                $ledger_entry->entry_type === 'Income' ? 'Expense' : 'Income',
                (float) $ledger_entry->amount,
                $reversalDescription,
                $ledger_entry->reference_type,
                $ledger_entry->reference_id,
            );
        });

        if ($error !== null) {
            return back()->withErrors(['ledger' => $error]);
        }

        return redirect()->route('ledger.index')->with('status', 'Ledger entry reversed.');
    }

    /**
     * @param  array{start_date:string,end_date:string,entry_type?:string}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return LedgerEntry::query()
            ->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']])
            ->when(
                isset($filters['entry_type']),
                fn ($query) => $query->where('entry_type', $filters['entry_type']),
            );
    }

    /**
     * @return array{start_date:string,end_date:string,entry_type?:string}
     */
    private function validateFilters(Request $request): array
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
        ]);

        return $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'entry_type' => ['nullable', Rule::in(['Income', 'Expense'])],
        ]);
    }
}
