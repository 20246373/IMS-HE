<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $query = LedgerEntry::with('recorder');

        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }
        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->entry_type);
        }

        $entries = $query->orderByDesc('entry_date')->paginate(50);

        $income = LedgerEntry::where('entry_type', 'income')->sum('amount');
        $expense = LedgerEntry::where('entry_type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return view('financial.index', [
            'entries' => $entries,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
        ]);
    }

    public function statements(Request $request)
    {
        $incomeQuery = LedgerEntry::where('entry_type', 'income')
            ->selectRaw('entry_date, SUM(amount) as total')
            ->groupBy('entry_date')
            ->orderBy('entry_date');

        $expenseQuery = LedgerEntry::where('entry_type', 'expense')
            ->selectRaw('entry_date, SUM(amount) as total')
            ->groupBy('entry_date')
            ->orderBy('entry_date');

        if ($request->filled('start_date')) {
            $incomeQuery->where('entry_date', '>=', $request->start_date);
            $expenseQuery->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $incomeQuery->where('entry_date', '<=', $request->end_date);
            $expenseQuery->where('entry_date', '<=', $request->end_date);
        }

        return view('financial.statements', [
            'income' => $incomeQuery->get(),
            'expense' => $expenseQuery->get(),
        ]);
    }

    public function cashFlow(Request $request)
    {
        $query = LedgerEntry::selectRaw('entry_date, entry_type, SUM(amount) as total')
            ->groupBy('entry_date', 'entry_type')
            ->orderBy('entry_date');

        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }

        return view('financial.cash-flow', ['cashFlow' => $query->get()]);
    }

    public function export(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $entries = LedgerEntry::with('recorder')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->orderBy('entry_date')
            ->get();

        return response()->streamDownload(function () use ($entries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Type', 'Amount', 'Description', 'Recorded By']);

            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->entry_date,
                    $entry->entry_type,
                    $entry->amount,
                    $entry->description,
                    $entry->recorder?->username ?? 'System',
                ]);
            }

            fclose($file);
        }, 'financial-export.csv', ['Content-Type' => 'text/csv']);
    }

    public function storeEntry(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'entry_type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string|max:30',
            'reference_id' => 'nullable|integer',
        ]);

        LedgerEntry::create([
            'entry_date' => $data['entry_date'],
            'entry_type' => $data['entry_type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('status', 'Financial entry recorded.');
    }

    public function updateEntry(Request $request, LedgerEntry $entry)
    {
        if ($entry->recorded_by !== auth()->id() && ! auth()->user()->hasRole('Admin')) {
            abort(403, 'You can only edit your own entries.');
        }

        $data = $request->validate([
            'entry_date' => 'required|date',
            'entry_type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $entry->update($data);

        return back()->with('status', 'Financial entry updated.');
    }

    public function destroyEntry(LedgerEntry $entry)
    {
        if ($entry->recorded_by !== auth()->id() && ! auth()->user()->hasRole('Admin')) {
            abort(403, 'You can only delete your own entries.');
        }

        $entry->delete();

        return back()->with('status', 'Financial entry deleted.');
    }
}
