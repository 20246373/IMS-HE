<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        $dates = $this->validateDates($request);

        return view('reports.financial', [
            'report' => $this->report($dates['start_date'], $dates['end_date']),
            'startDate' => $dates['start_date'],
            'endDate' => $dates['end_date'],
        ]);
    }

    public function download(Request $request): Response
    {
        $dates = $this->validateDates($request);

        return Pdf::loadView('reports.pdf.financial', [
            'report' => $this->report($dates['start_date'], $dates['end_date']),
            'startDate' => $dates['start_date'],
            'endDate' => $dates['end_date'],
        ])->download("financial-report-{$dates['start_date']}-to-{$dates['end_date']}.pdf");
    }

    /**
     * @return array{days:array<int,array{date:string,income:float,expense:float,net:float},totalIncome:float,totalExpense:float,totalNet:float}
     */
    private function report(string $startDate, string $endDate): array
    {
        $entries = LedgerEntry::query()
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn (LedgerEntry $entry): string => $entry->entry_date->toDateString());
        $days = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dayEntries = $entries->get($date->toDateString(), collect());
            $income = (float) $dayEntries->where('entry_type', 'Income')->sum('amount');
            $expense = (float) $dayEntries->where('entry_type', 'Expense')->sum('amount');
            $days[] = [
                'date' => $date->toDateString(),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'net' => round($income - $expense, 2),
            ];
        }

        $totalIncome = round(collect($days)->sum('income'), 2);
        $totalExpense = round(collect($days)->sum('expense'), 2);

        return [
            'days' => $days,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalNet' => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * @return array{start_date:string,end_date:string}
     */
    private function validateDates(Request $request): array
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
        ]);

        return $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
    }
}
