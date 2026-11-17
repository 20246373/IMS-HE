<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $filters = $this->validateSalesDates($request);
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;

        return view('reports.sales', [
            'report' => $this->getSalesReport($filters['start_date'], $filters['end_date'], $categoryId),
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'categories' => Category::orderBy('category_name')->get(),
            'categoryId' => $categoryId,
        ]);
    }

    public function downloadSales(Request $request)
    {
        $filters = $this->validateSalesDates($request);
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $report = $this->getSalesReport($filters['start_date'], $filters['end_date'], $categoryId);

        return Pdf::loadView('reports.pdf.sales', [
            'report' => $report,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'categoryId' => $categoryId,
        ])->download("sales-report-{$filters['start_date']}-to-{$filters['end_date']}.pdf");
    }

    /**
     * Completed sales are invoices whose status is Completed.
     *
     * @return array{totalRevenue: int|float|string, totalTransactions: int, sales: Collection, categorySales: Collection}
     */
    public function getSalesReport($startDate, $endDate, ?int $categoryId = null): array
    {
        $completedSales = DB::table('invoices')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'Completed')
            ->when($categoryId !== null, function ($query) use ($categoryId): void {
                $query->whereExists(function ($query) use ($categoryId): void {
                    $query->selectRaw('1')
                        ->from('invoice_items')
                        ->join('products', 'products.product_id', '=', 'invoice_items.product_id')
                        ->whereColumn('invoice_items.invoice_id', 'invoices.invoice_id')
                        ->where('products.category_id', $categoryId);
                });
            });

        if ($categoryId === null) {
            $summary = (clone $completedSales)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(invoice_id) as total_transactions')
                ->first();
        } else {
            $summary = DB::table('invoice_items')
                ->join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
                ->join('products', 'products.product_id', '=', 'invoice_items.product_id')
                ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
                ->where('invoices.status', 'Completed')
                ->where('products.category_id', $categoryId)
                ->selectRaw('COALESCE(SUM(invoice_items.subtotal), 0) as total_revenue, COUNT(DISTINCT invoices.invoice_id) as total_transactions')
                ->first();
        }

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

        $categorySales = DB::table('invoice_items')
            ->join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.product_id', '=', 'invoice_items.product_id')
            ->join('categories', 'categories.category_id', '=', 'products.category_id')
            ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
            ->where('invoices.status', 'Completed')
            ->when($categoryId !== null, fn ($query) => $query->where('products.category_id', $categoryId))
            ->select([
                'categories.category_id',
                'categories.category_name',
            ])
            ->selectRaw('SUM(invoice_items.quantity) as units')
            ->selectRaw('COALESCE(SUM(invoice_items.subtotal), 0) as revenue')
            ->groupBy('categories.category_id', 'categories.category_name')
            ->orderBy('categories.category_name')
            ->get();

        return [
            'totalRevenue' => $summary->total_revenue,
            'totalTransactions' => (int) $summary->total_transactions,
            'sales' => $sales,
            'categorySales' => $categorySales,
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
            'category_id' => ['nullable', 'integer', 'exists:categories,category_id'],
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
