<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Payroll;
use App\Models\Supplier;
use App\Services\FinancialService;
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

    public function inventory(Request $request)
    {
        $categoryId = $this->validateInventoryFilter($request);

        return view('reports.inventory', [
            'report' => $this->getInventoryReport($categoryId),
            'categories' => Category::orderBy('category_name')->get(),
            'categoryId' => $categoryId,
        ]);
    }

    public function downloadInventory(Request $request)
    {
        $categoryId = $this->validateInventoryFilter($request);

        return Pdf::loadView('reports.pdf.inventory', [
            'report' => $this->getInventoryReport($categoryId),
        ])->setPaper('a4', 'landscape')->download('inventory-report.pdf');
    }

    public function procurement(Request $request)
    {
        $filters = $this->validateProcurementFilters($request);
        $supplierId = isset($filters['supplier_id']) ? (int) $filters['supplier_id'] : null;
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;

        return view('reports.procurement', [
            'report' => $this->getProcurementReport(
                $filters['start_date'],
                $filters['end_date'],
                $supplierId,
                $categoryId,
            ),
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'supplierId' => $supplierId,
            'categoryId' => $categoryId,
            'suppliers' => Supplier::orderBy('supplier_name')->get(),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function downloadProcurement(Request $request)
    {
        $filters = $this->validateProcurementFilters($request);
        $supplierId = isset($filters['supplier_id']) ? (int) $filters['supplier_id'] : null;
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;

        return Pdf::loadView('reports.pdf.procurement', [
            'report' => $this->getProcurementReport(
                $filters['start_date'],
                $filters['end_date'],
                $supplierId,
                $categoryId,
            ),
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'supplierId' => $supplierId,
            'categoryId' => $categoryId,
        ])->setPaper('a4', 'landscape')
            ->download("procurement-report-{$filters['start_date']}-to-{$filters['end_date']}.pdf");
    }

    /**
     * Payroll report (SDD Module 4.1 Report Generation): one batch's payslips
     * with totals, or every batch when no payroll is selected.
     */
    public function payroll(Request $request)
    {
        $payroll = $this->resolvePayroll($request);

        return view('reports.payroll', [
            'payroll' => $payroll?->load('processor', 'payslips.employee'),
            'payrolls' => Payroll::orderByDesc('period_start')->get(),
        ]);
    }

    public function downloadPayroll(Request $request)
    {
        $payroll = $this->resolvePayroll($request);

        abort_if($payroll === null, 404);

        return Pdf::loadView('reports.pdf.payroll', [
            'payroll' => $payroll->load('processor', 'payslips.employee'),
        ])->setPaper('a4', 'landscape')->download("payroll-report-{$payroll->payroll_id}.pdf");
    }

    private function resolvePayroll(Request $request): ?Payroll
    {
        $data = $request->validate([
            'payroll_id' => ['nullable', 'integer', 'exists:payrolls,payroll_id'],
        ]);

        if (! empty($data['payroll_id'])) {
            return Payroll::findOrFail((int) $data['payroll_id']);
        }

        return Payroll::orderByDesc('period_start')->orderByDesc('payroll_id')->first();
    }

    /** Financial report (SDD 5): ledger entries plus the cash-flow summary for the range. */
    public function financial(Request $request)
    {
        $filters = $this->validateFinancialFilters($request);
        $financial = app(FinancialService::class);

        return view('reports.financial', [
            'filters' => $filters,
            'entries' => LedgerEntry::with('recorder')
                ->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']])
                ->orderBy('entry_date')->orderBy('ledger_id')->get(),
            'cashFlow' => $financial->cashFlow($filters['start_date'], $filters['end_date']),
        ]);
    }

    public function downloadFinancial(Request $request)
    {
        $filters = $this->validateFinancialFilters($request);
        $financial = app(FinancialService::class);

        return Pdf::loadView('reports.pdf.financial', [
            'filters' => $filters,
            'entries' => LedgerEntry::with('recorder')
                ->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']])
                ->orderBy('entry_date')->orderBy('ledger_id')->get(),
            'cashFlow' => $financial->cashFlow($filters['start_date'], $filters['end_date']),
        ])->setPaper('a4', 'landscape')
            ->download("financial-report-{$filters['start_date']}-to-{$filters['end_date']}.pdf");
    }

    /** Attendance report (SRS report list): days and overtime per employee for the range. */
    public function attendance(Request $request)
    {
        $filters = $this->validateFinancialFilters($request);

        return view('reports.attendance', [
            'filters' => $filters,
            'report' => $this->getAttendanceReport($filters['start_date'], $filters['end_date']),
        ]);
    }

    public function downloadAttendance(Request $request)
    {
        $filters = $this->validateFinancialFilters($request);

        return Pdf::loadView('reports.pdf.attendance', [
            'filters' => $filters,
            'report' => $this->getAttendanceReport($filters['start_date'], $filters['end_date']),
        ])->setPaper('a4', 'landscape')
            ->download("attendance-report-{$filters['start_date']}-to-{$filters['end_date']}.pdf");
    }

    /**
     * @return array{rows: Collection, totalDays: float, totalOvertime: float}
     */
    public function getAttendanceReport(string $startDate, string $endDate): array
    {
        $rows = DB::table('attendance')
            ->join('employees', 'employees.employee_id', '=', 'attendance.employee_id')
            ->whereBetween('attendance.work_date', [$startDate, $endDate])
            ->select([
                'employees.employee_id',
                'employees.first_name',
                'employees.last_name',
                'employees.position',
            ])
            ->selectRaw('COUNT(attendance.attendance_id) as days_worked')
            ->selectRaw('COALESCE(SUM(attendance.overtime_hours),0) as overtime_hours')
            ->selectRaw('COUNT(attendance.time_out) as days_clocked_out')
            ->groupBy(['employees.employee_id', 'employees.first_name', 'employees.last_name', 'employees.position'])
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->get();

        return [
            'rows' => $rows,
            'totalDays' => (float) $rows->sum('days_worked'),
            'totalOvertime' => round((float) $rows->sum('overtime_hours'), 2),
        ];
    }

    /** @return array{start_date: string, end_date: string} */
    private function validateFinancialFilters(Request $request): array
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

    private function validateInventoryFilter(Request $request): ?int
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,category_id'],
        ]);

        return isset($data['category_id']) ? (int) $data['category_id'] : null;
    }

    /**
     * @return array{start_date: string, end_date: string, supplier_id?: int|string|null, category_id?: int|string|null}
     */
    private function validateProcurementFilters(Request $request): array
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
        ]);

        return $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,supplier_id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,category_id'],
        ]);
    }

    /**
     * @return array{products: Collection, lowStockCount: int, totalValuation: int|float|string}
     */
    public function getInventoryReport(?int $categoryId = null): array
    {
        $products = DB::table('products')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->when($categoryId !== null, fn ($query) => $query->where('products.category_id', $categoryId))
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
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
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

    /**
     * @return array{
     *     summary: array{purchaseOrderCount: int, orderedValue: int|float|string, receivedValue: int|float|string, deliveryCount: int, deliveredUnits: int|float|string},
     *     purchaseOrders: Collection,
     *     orderItems: Collection,
     *     deliveries: Collection,
     *     supplierSpend: Collection
     * }
     */
    public function getProcurementReport(
        string $startDate,
        string $endDate,
        ?int $supplierId = null,
        ?int $categoryId = null,
    ): array {
        $orderLines = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.po_id', '=', 'purchase_order_items.po_id')
            ->join('suppliers', 'suppliers.supplier_id', '=', 'purchase_orders.supplier_id')
            ->join('products', 'products.product_id', '=', 'purchase_order_items.product_id')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->when($supplierId !== null, fn ($query) => $query->where('purchase_orders.supplier_id', $supplierId))
            ->when($categoryId !== null, fn ($query) => $query->where('products.category_id', $categoryId));

        $purchaseOrders = (clone $orderLines)
            ->select([
                'purchase_orders.po_id',
                'purchase_orders.order_date',
                'purchase_orders.status',
                'suppliers.supplier_name',
            ])
            ->selectRaw('SUM(purchase_order_items.quantity) as ordered_quantity')
            ->selectRaw('SUM(purchase_order_items.quantity_received) as received_quantity')
            ->selectRaw('COALESCE(SUM(purchase_order_items.subtotal), 0) as ordered_value')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity_received * purchase_order_items.unit_cost), 0) as received_value')
            ->groupBy([
                'purchase_orders.po_id',
                'purchase_orders.order_date',
                'purchase_orders.status',
                'suppliers.supplier_name',
            ])
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_orders.po_id')
            ->get();

        $orderItems = (clone $orderLines)
            ->select([
                'purchase_orders.po_id',
                'purchase_orders.order_date',
                'suppliers.supplier_name',
                'products.product_name',
                'categories.category_name',
                'purchase_order_items.quantity',
                'purchase_order_items.quantity_received',
                'purchase_order_items.unit_cost',
                'purchase_order_items.subtotal',
            ])
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_orders.po_id')
            ->orderBy('products.product_name')
            ->get();

        $deliveries = DB::table('inventory_transactions')
            ->join('purchase_orders', 'purchase_orders.po_id', '=', 'inventory_transactions.reference_id')
            ->join('suppliers', 'suppliers.supplier_id', '=', 'purchase_orders.supplier_id')
            ->join('products', 'products.product_id', '=', 'inventory_transactions.product_id')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->where('inventory_transactions.reference_type', 'PO')
            ->where('inventory_transactions.transaction_type', 'IN')
            ->whereBetween('inventory_transactions.transaction_date', [$startDate, $endDate])
            ->when($supplierId !== null, fn ($query) => $query->where('purchase_orders.supplier_id', $supplierId))
            ->when($categoryId !== null, fn ($query) => $query->where('products.category_id', $categoryId))
            ->select([
                'inventory_transactions.transaction_id',
                'inventory_transactions.transaction_date',
                'inventory_transactions.quantity',
                'purchase_orders.po_id',
                'suppliers.supplier_name',
                'products.product_name',
                'categories.category_name',
            ])
            ->orderByDesc('inventory_transactions.transaction_date')
            ->orderByDesc('inventory_transactions.transaction_id')
            ->get();

        $supplierSpend = (clone $orderLines)
            ->select([
                'suppliers.supplier_id',
                'suppliers.supplier_name',
            ])
            ->selectRaw('COUNT(DISTINCT purchase_orders.po_id) as po_count')
            ->selectRaw('COALESCE(SUM(purchase_order_items.subtotal), 0) as ordered_value')
            ->selectRaw('COALESCE(SUM(purchase_order_items.quantity_received * purchase_order_items.unit_cost), 0) as received_value')
            ->groupBy([
                'suppliers.supplier_id',
                'suppliers.supplier_name',
            ])
            ->orderByDesc('ordered_value')
            ->get();

        return [
            'summary' => [
                'purchaseOrderCount' => $purchaseOrders->count(),
                'orderedValue' => $supplierSpend->sum('ordered_value'),
                'receivedValue' => $supplierSpend->sum('received_value'),
                'deliveryCount' => $deliveries->count(),
                'deliveredUnits' => $deliveries->sum('quantity'),
            ],
            'purchaseOrders' => $purchaseOrders,
            'orderItems' => $orderItems,
            'deliveries' => $deliveries,
            'supplierSpend' => $supplierSpend,
        ];
    }
}
