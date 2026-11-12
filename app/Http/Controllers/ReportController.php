<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $dates = $this->validateSalesDates($request);

        return view('reports.sales', [
            'report' => $this->getSalesReport($dates['start_date'], $dates['end_date']),
            'startDate' => $dates['start_date'],
            'endDate' => $dates['end_date'],
        ]);
    }

    public function downloadSales(Request $request)
    {
        $dates = $this->validateSalesDates($request);
        $report = $this->getSalesReport($dates['start_date'], $dates['end_date']);

        return Pdf::loadView('reports.pdf.sales', [
            'report' => $report,
            'startDate' => $dates['start_date'],
            'endDate' => $dates['end_date'],
        ])->download("sales-report-{$dates['start_date']}-to-{$dates['end_date']}.pdf");
    }

    /**
     * Completed sales are invoices with a recorded payment.
     *
     * @return array{totalRevenue: int|float|string, totalTransactions: int, sales: Collection}
     */
    public function getSalesReport($startDate, $endDate): array
    {
        $completedSales = DB::table('invoices')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.invoice_id', 'invoices.invoice_id');
            });

        $summary = (clone $completedSales)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(invoice_id) as total_transactions')
            ->first();

        $sales = (clone $completedSales)
            ->leftJoin('customers', 'customers.customer_id', '=', 'invoices.customer_id')
            ->select([
                'invoices.invoice_id',
                'invoices.invoice_date',
                'invoices.total_amount',
                'customers.first_name',
                'customers.last_name',
            ])
            ->orderByDesc('invoices.invoice_date')
            ->orderByDesc('invoices.invoice_id')
            ->get();

        return [
            'totalRevenue' => $summary->total_revenue,
            'totalTransactions' => (int) $summary->total_transactions,
            'sales' => $sales,
        ];
    }

    public function inventory()
    {
        return view('reports.inventory', [
            'report' => $this->getInventoryReport(),
        ]);
    }

    public function downloadInventory()
    {
        return Pdf::loadView('reports.pdf.inventory', [
            'report' => $this->getInventoryReport(),
        ])->setPaper('a4', 'landscape')->download('inventory-report.pdf');
    }

    /**
     * @return array{start_date: string, end_date: string}
     */
    private function validateSalesDates(Request $request): array
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

    /**
     * @return array{products: Collection, lowStockCount: int, totalValuation: int|float|string}
     */
    public function getInventoryReport(): array
    {
        $products = DB::table('products')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->select([
                'products.product_id',
                'products.product_name',
                'products.stock_quantity',
                'products.reorder_level',
                'products.unit_price',
                'products.is_active',
                'categories.category_name',
            ])
            ->selectRaw('(products.stock_quantity * products.unit_price) as stock_value')
            ->orderBy('products.product_name')
            ->get();

        $summary = DB::table('products')
            ->selectRaw(
                'COALESCE(SUM(stock_quantity * unit_price), 0) as total_valuation, '.
                'COALESCE(SUM(CASE WHEN stock_quantity <= reorder_level THEN 1 ELSE 0 END), 0) as low_stock_count'
            )
            ->first();

        return [
            'products' => $products,
            'lowStockCount' => (int) $summary->low_stock_count,
            'totalValuation' => $summary->total_valuation,
        ];
    }
}
