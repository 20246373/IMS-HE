<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $sales = Invoice::with(['customer', 'employee'])
            ->when($startDate, fn ($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('invoice_date', '<=', $endDate))
            ->orderByDesc('invoice_date')
            ->get();

        $totalSales = $sales->sum('total_amount');
        $totalTransactions = $sales->count();

        return view('reports.sales', [
            'sales' => $sales,
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function inventory(Request $request)
    {
        $products = Product::with('category')
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->orderByRaw('(stock_quantity <= reorder_level) desc')->orderBy('product_name')
            ->get();

        $totalStock = $products->sum('stock_quantity');
        $totalValue = $products->sum(fn ($p) => $p->stock_quantity * $p->unit_price);
        $lowStockCount = $products->filter(fn ($p) => $p->isLowStock())->count();

        return view('reports.inventory', [
            'products' => $products,
            'totalStock' => $totalStock,
            'totalValue' => $totalValue,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    public function financial(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $income = LedgerEntry::where('entry_type', 'income')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $expense = LedgerEntry::where('entry_type', 'expense')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $net = $income - $expense;

        return view('reports.financial', [
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function payroll(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $payrolls = Payroll::with('processor')
            ->when($startDate, fn ($q) => $q->where('period_start', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('period_end', '<=', $endDate))
            ->orderByDesc('period_start')
            ->get();

        $totalGross = $payrolls->sum('total_gross');
        $totalNet = $payrolls->sum('total_net');

        return view('reports.payroll', [
            'payrolls' => $payrolls,
            'totalGross' => $totalGross,
            'totalNet' => $totalNet,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function company(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $income = LedgerEntry::where('entry_type', 'income')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $expense = LedgerEntry::where('entry_type', 'expense')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $netProfit = $income - $expense;

        $salesCount = Invoice::when($startDate, fn ($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('invoice_date', '<=', $endDate))
            ->count();

        return view('reports.company', [
            'income' => $income,
            'expense' => $expense,
            'netProfit' => $netProfit,
            'salesCount' => $salesCount,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(Request $request, $type)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return match ($type) {
            'sales' => $this->exportSales($startDate, $endDate),
            'inventory' => $this->exportInventory(),
            'company' => $this->exportCompany($startDate, $endDate),
            default => back()->withErrors(['type' => 'Invalid report type.']),
        };
    }

    private function exportSales($startDate, $endDate)
    {
        $sales = Invoice::with(['customer', 'employee'])
            ->when($startDate, fn ($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('invoice_date', '<=', $endDate))
            ->orderBy('invoice_date')
            ->get();

        return response()->streamDownload(function () use ($sales) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Invoice ID', 'Customer', 'Employee', 'Total Amount', 'Status']);

            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->invoice_date,
                    $sale->invoice_id,
                    $sale->customer?->getFullNameAttribute() ?? 'Walk-in',
                    $sale->employee?->getFullNameAttribute() ?? 'N/A',
                    $sale->total_amount,
                    $sale->status,
                ]);
            }

            fclose($file);
        }, 'sales-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function exportInventory()
    {
        $products = Product::with('category')->orderBy('product_name')->get();

        return response()->streamDownload(function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Product', 'Category', 'Stock Quantity', 'Reorder Level', 'Unit Price', 'Total Value']);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->product_name,
                    $product->category?->category_name ?? 'N/A',
                    $product->stock_quantity,
                    $product->reorder_level,
                    $product->unit_price,
                    $product->stock_quantity * $product->unit_price,
                ]);
            }

            fclose($file);
        }, 'inventory-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function exportCompany($startDate, $endDate)
    {
        $income = LedgerEntry::where('entry_type', 'income')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $expense = LedgerEntry::where('entry_type', 'expense')
            ->when($startDate, fn ($q) => $q->where('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('entry_date', '<=', $endDate))
            ->sum('amount');

        $salesCount = Invoice::when($startDate, fn ($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('invoice_date', '<=', $endDate))
            ->count();

        return response()->streamDownload(function () use ($income, $expense, $salesCount, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Report', 'Value']);
            fputcsv($file, ['Period Start', $startDate ?? 'All']);
            fputcsv($file, ['Period End', $endDate ?? 'All']);
            fputcsv($file, ['Total Income', $income]);
            fputcsv($file, ['Total Expenses', $expense]);
            fputcsv($file, ['Net Profit', $income - $expense]);
            fputcsv($file, ['Total Sales Transactions', $salesCount]);

            fclose($file);
        }, 'company-report.csv', ['Content-Type' => 'text/csv']);
    }
}
