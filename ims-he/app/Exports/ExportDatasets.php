<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportDatasets
{
    /**
     * The only statuses each table can hold, for the Status filter dropdown.
     *
     * @var array<string, list<string>>
     */
    /** Datasets only the Administrator may export (accounts and the audit log). */
    public const ADMIN_ONLY = 'admin';

    public const STATUS_OPTIONS = [
        'purchase_orders' => ['Pending', 'Delivered', 'Cancelled'],
        'invoices' => ['Pending', 'Completed', 'Cancelled'],
    ];

    public static function all(): array
    {
        return [
            self::dataset('categories', 'Categories', 'exports.inventory', [], ['category_id', 'category_name'],
                fn (Request $r) => DB::table('categories')->select(['category_id', 'category_name'])->orderBy('category_id')->cursor()),

            self::dataset('products', 'Products', 'exports.inventory', ['category'], ['product_id', 'product_name', 'brand', 'category', 'supplier', 'unit_price', 'stock_quantity', 'reorder_level', 'is_active'],
                fn (Request $r) => self::query($r, DB::table('products')
                    ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
                    ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'products.supplier_id')
                    ->select(['products.product_id', 'products.product_name', 'products.brand', 'categories.category_name as category', 'suppliers.supplier_name as supplier', 'products.unit_price', 'products.stock_quantity', 'products.reorder_level', 'products.is_active'])
                    ->orderBy('products.product_id'), 'products.category_id')
                    ->cursor()->map(fn ($row) => [$row->product_id, $row->product_name, $row->brand, $row->category, $row->supplier, number_format((float) $row->unit_price, 2, '.', ''), $row->stock_quantity, $row->reorder_level, $row->is_active ? '1' : '0'])),

            self::dataset('inventory_transactions', 'Inventory transactions', 'exports.inventory', ['dates', 'category'], ['transaction_id', 'transaction_date', 'product_name', 'category', 'transaction_type', 'quantity', 'reference_type', 'reference_id'],
                fn (Request $r) => self::query($r, DB::table('inventory_transactions')
                    ->join('products', 'products.product_id', '=', 'inventory_transactions.product_id')
                    ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
                    ->select(['inventory_transactions.transaction_id', 'inventory_transactions.transaction_date', 'products.product_name', 'categories.category_name as category', 'inventory_transactions.transaction_type', 'inventory_transactions.quantity', 'inventory_transactions.reference_type', 'inventory_transactions.reference_id'])
                    ->orderBy('inventory_transactions.transaction_date'), 'products.category_id', 'inventory_transactions.transaction_date')
                    ->cursor()->map(fn ($row) => [$row->transaction_id, $row->transaction_date, $row->product_name, $row->category, $row->transaction_type, $row->quantity, $row->reference_type, $row->reference_id])),

            self::dataset('suppliers', 'Suppliers', 'exports.suppliers', [], ['supplier_id', 'supplier_name', 'contact_number', 'address'],
                fn (Request $r) => DB::table('suppliers')->select(['supplier_id', 'supplier_name', 'contact_number', 'address'])->orderBy('supplier_id')->cursor()),

            self::dataset('purchase_orders', 'Purchase orders', 'exports.inventory', ['dates', 'status'], ['po_id', 'order_date', 'supplier_name', 'status', 'is_auto', 'total'],
                fn (Request $r) => self::query($r, DB::table('purchase_orders')
                    ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'purchase_orders.supplier_id')
                    ->leftJoinSub(DB::table('purchase_order_items')->selectRaw('po_id, SUM(subtotal) as total')->groupBy('po_id'), 'po_totals', 'po_totals.po_id', '=', 'purchase_orders.po_id')
                    ->select(['purchase_orders.po_id', 'purchase_orders.order_date', 'suppliers.supplier_name', 'purchase_orders.status', 'purchase_orders.is_auto', 'po_totals.total'])
                    ->orderBy('purchase_orders.order_date'), null, 'purchase_orders.order_date')
                    ->cursor()->map(fn ($row) => [$row->po_id, $row->order_date, $row->supplier_name, $row->status, $row->is_auto ? '1' : '0', number_format((float) ($row->total ?? 0), 2, '.', '')])),

            self::dataset('purchase_order_items', 'Purchase order items', 'exports.inventory', ['dates', 'category'], ['po_id', 'order_date', 'supplier_name', 'product_name', 'category', 'quantity', 'quantity_received', 'unit_cost', 'subtotal'],
                fn (Request $r) => self::query($r, DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_orders.po_id', '=', 'purchase_order_items.po_id')
                    ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'purchase_orders.supplier_id')
                    ->join('products', 'products.product_id', '=', 'purchase_order_items.product_id')
                    ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
                    ->select(['purchase_orders.po_id', 'purchase_orders.order_date', 'suppliers.supplier_name', 'products.product_name', 'categories.category_name as category', 'purchase_order_items.quantity', 'purchase_order_items.quantity_received', 'purchase_order_items.unit_cost', 'purchase_order_items.subtotal'])
                    ->orderBy('purchase_orders.order_date'), 'products.category_id', 'purchase_orders.order_date')
                    ->cursor()->map(fn ($row) => [$row->po_id, $row->order_date, $row->supplier_name, $row->product_name, $row->category, $row->quantity, $row->quantity_received, number_format((float) $row->unit_cost, 2, '.', ''), number_format((float) $row->subtotal, 2, '.', '')])),

            self::dataset('invoices', 'Invoices', 'exports.sales', ['dates', 'status'], ['invoice_id', 'invoice_date', 'channel', 'status', 'customer_email', 'cashier', 'total_amount', 'payment_method'],
                fn (Request $r) => self::query($r, DB::table('invoices')
                    ->leftJoin('customers', 'customers.customer_id', '=', 'invoices.customer_id')
                    ->leftJoin('employees', 'employees.employee_id', '=', 'invoices.employee_id')
                    ->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.invoice_id')
                    ->select(['invoices.invoice_id', 'invoices.invoice_date', 'invoices.channel', 'invoices.status', 'customers.email as customer_email', 'employees.first_name as cashier_first_name', 'employees.last_name as cashier_last_name', 'invoices.total_amount', 'payments.payment_method'])
                    ->orderBy('invoices.invoice_date'), null, 'invoices.invoice_date')
                    ->cursor()->map(fn ($row) => [$row->invoice_id, $row->invoice_date, $row->channel, $row->status, $row->customer_email, trim(($row->cashier_first_name ?? '').' '.($row->cashier_last_name ?? '')), number_format((float) $row->total_amount, 2, '.', ''), $row->payment_method])),

            self::dataset('invoice_items', 'Invoice items', 'exports.sales', ['dates', 'category'], ['invoice_id', 'invoice_date', 'product_name', 'category', 'quantity', 'price', 'subtotal'],
                fn (Request $r) => self::query($r, DB::table('invoice_items')
                    ->join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
                    ->join('products', 'products.product_id', '=', 'invoice_items.product_id')
                    ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
                    ->select(['invoices.invoice_id', 'invoices.invoice_date', 'products.product_name', 'categories.category_name as category', 'invoice_items.quantity', 'invoice_items.price', 'invoice_items.subtotal'])
                    ->orderBy('invoices.invoice_date'), 'products.category_id', 'invoices.invoice_date')
                    ->cursor()->map(fn ($row) => [$row->invoice_id, $row->invoice_date, $row->product_name, $row->category, $row->quantity, number_format((float) $row->price, 2, '.', ''), number_format((float) $row->subtotal, 2, '.', '')])),

            self::dataset('payments', 'Payments', 'exports.sales', ['dates'], ['payment_id', 'invoice_id', 'payment_date', 'payment_method', 'amount_paid', 'change_amount'],
                fn (Request $r) => self::query($r, DB::table('payments')->select(['payment_id', 'invoice_id', 'payment_date', 'payment_method', 'amount_paid', 'change_amount'])->orderBy('payment_date'), null, 'payment_date')
                    ->cursor()->map(fn ($row) => [$row->payment_id, $row->invoice_id, $row->payment_date, $row->payment_method, number_format((float) $row->amount_paid, 2, '.', ''), number_format((float) $row->change_amount, 2, '.', '')])),

            self::dataset('ledger_entries', 'Ledger entries', 'exports.payroll', ['dates'], ['ledger_id', 'entry_date', 'entry_type', 'amount', 'description', 'reference_type', 'reference_id', 'recorded_by'],
                fn (Request $r) => self::query($r, DB::table('ledger_entries')
                    ->leftJoin('users', 'users.user_id', '=', 'ledger_entries.recorded_by')
                    ->selectRaw('ledger_entries.ledger_id, ledger_entries.entry_date, ledger_entries.entry_type, ledger_entries.amount, ledger_entries.description, ledger_entries.reference_type, ledger_entries.reference_id, COALESCE(users.email, \'System\') as recorded_by')
                    ->orderBy('ledger_entries.entry_date'), null, 'ledger_entries.entry_date')
                    ->cursor()->map(fn ($row) => [$row->ledger_id, $row->entry_date, $row->entry_type, number_format((float) $row->amount, 2, '.', ''), $row->description, $row->reference_type, $row->reference_id, $row->recorded_by ?: 'System'])),

            self::dataset('employees', 'Employees', 'exports.payroll', [], ['employee_id', 'first_name', 'last_name', 'position', 'daily_rate', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no', 'date_hired', 'status', 'account_email'],
                fn (Request $r) => DB::table('employees')->leftJoin('users', 'users.employee_id', '=', 'employees.employee_id')
                    ->selectRaw('employees.employee_id, employees.first_name, employees.last_name, employees.position, employees.daily_rate, employees.sss_no, employees.philhealth_no, employees.pagibig_no, employees.tin_no, employees.date_hired, employees.status, users.email as account_email')
                    ->orderBy('employees.employee_id')->cursor()->map(fn ($row) => [$row->employee_id, $row->first_name, $row->last_name, $row->position, number_format((float) $row->daily_rate, 2, '.', ''), $row->sss_no, $row->philhealth_no, $row->pagibig_no, $row->tin_no, $row->date_hired, $row->status, $row->account_email])),

            self::dataset('attendance', 'Attendance', 'exports.payroll', ['dates'], ['attendance_id', 'work_date', 'employee_name', 'time_in', 'time_out', 'overtime_hours'],
                fn (Request $r) => self::query($r, DB::table('attendance')
                    ->join('employees', 'employees.employee_id', '=', 'attendance.employee_id')
                    ->select(['attendance.attendance_id', 'attendance.work_date', 'employees.first_name as employee_first_name', 'employees.last_name as employee_last_name', 'attendance.time_in', 'attendance.time_out', 'attendance.overtime_hours'])
                    ->orderBy('attendance.work_date'), null, 'attendance.work_date')
                    ->cursor()->map(fn ($row) => [$row->attendance_id, $row->work_date, trim($row->employee_first_name.' '.$row->employee_last_name), $row->time_in, $row->time_out, number_format((float) $row->overtime_hours, 2, '.', '')])),

            self::dataset('payrolls', 'Payrolls', 'exports.payroll', ['dates'], ['payroll_id', 'period_start', 'period_end', 'status', 'total_gross', 'total_deductions', 'total_net', 'processed_by'],
                fn (Request $r) => self::query($r, DB::table('payrolls')
                    ->leftJoin('users', 'users.user_id', '=', 'payrolls.processed_by')
                    ->selectRaw('payrolls.payroll_id, payrolls.period_start, payrolls.period_end, payrolls.status, payrolls.total_gross, payrolls.total_deductions, payrolls.total_net, users.email as processed_by')
                    ->orderBy('payrolls.period_start'), null, 'payrolls.period_start')
                    ->cursor()->map(fn ($row) => [$row->payroll_id, $row->period_start, $row->period_end, $row->status, number_format((float) $row->total_gross, 2, '.', ''), number_format((float) $row->total_deductions, 2, '.', ''), number_format((float) $row->total_net, 2, '.', ''), $row->processed_by])),

            self::dataset('payslips', 'Payslips', 'exports.payroll', ['dates'], ['payslip_id', 'payroll_id', 'period_start', 'period_end', 'employee_name', 'days_worked', 'overtime_hours', 'gross_pay', 'sss_deduction', 'philhealth_deduction', 'pagibig_deduction', 'tax_deduction', 'other_deductions', 'total_deductions', 'net_pay'],
                fn (Request $r) => self::query($r, DB::table('payslips')
                    ->join('payrolls', 'payrolls.payroll_id', '=', 'payslips.payroll_id')
                    ->join('employees', 'employees.employee_id', '=', 'payslips.employee_id')
                    ->select(['payslips.payslip_id', 'payslips.payroll_id', 'payrolls.period_start', 'payrolls.period_end', 'employees.first_name as employee_first_name', 'employees.last_name as employee_last_name', 'payslips.days_worked', 'payslips.overtime_hours', 'payslips.gross_pay', 'payslips.sss_deduction', 'payslips.philhealth_deduction', 'payslips.pagibig_deduction', 'payslips.tax_deduction', 'payslips.other_deductions', 'payslips.total_deductions', 'payslips.net_pay'])
                    ->orderBy('payrolls.period_start'), null, 'payrolls.period_start')
                    ->cursor()->map(function ($row) {
                        return [$row->payslip_id, $row->payroll_id, $row->period_start, $row->period_end, trim($row->employee_first_name.' '.$row->employee_last_name), $row->days_worked, $row->overtime_hours, number_format((float) $row->gross_pay, 2, '.', ''), number_format((float) $row->sss_deduction, 2, '.', ''), number_format((float) $row->philhealth_deduction, 2, '.', ''), number_format((float) $row->pagibig_deduction, 2, '.', ''), number_format((float) $row->tax_deduction, 2, '.', ''), number_format((float) $row->other_deductions, 2, '.', ''), number_format((float) $row->total_deductions, 2, '.', ''), number_format((float) $row->net_pay, 2, '.', '')];
                    })),

            self::dataset('customers', 'Customers', 'exports.customers', [], ['customer_id', 'email', 'first_name', 'last_name', 'contact_number', 'address', 'email_verified_at', 'created_at'],
                fn (Request $r) => DB::table('customers')->select(['customer_id', 'email', 'first_name', 'last_name', 'contact_number', 'address', 'email_verified_at', 'created_at'])->orderBy('customer_id')->cursor()),

            self::dataset('users', 'Users', self::ADMIN_ONLY, [], ['user_id', 'email', 'role', 'status', 'employee_name', 'last_login', 'created_at'],
                fn (Request $r) => DB::table('users')->leftJoin('employees', 'employees.employee_id', '=', 'users.employee_id')
                    ->select(['users.user_id', 'users.email', 'users.role', 'users.status', 'employees.first_name as employee_first_name', 'employees.last_name as employee_last_name', 'users.last_login', 'users.created_at'])
                    ->orderBy('users.user_id')->cursor()->map(fn ($row) => [$row->user_id, $row->email, $row->role, $row->status, trim(($row->employee_first_name ?? '').' '.($row->employee_last_name ?? '')), $row->last_login, $row->created_at])),

            self::dataset('audit_logs', 'Audit logs', self::ADMIN_ONLY, ['dates'], ['log_id', 'logged_at', 'user', 'action', 'table_name', 'record_id'],
                fn (Request $r) => self::query($r, DB::table('audit_logs')
                    ->leftJoin('users', 'users.user_id', '=', 'audit_logs.user_id')
                    ->selectRaw('audit_logs.log_id, audit_logs.logged_at, COALESCE(users.email, \'System\') as audit_user, audit_logs.action, audit_logs.table_name, audit_logs.record_id')
                    ->orderBy('audit_logs.logged_at'), null, 'audit_logs.logged_at')
                    ->cursor()->map(fn ($row) => [$row->log_id, $row->logged_at, $row->audit_user ?: 'System', $row->action, $row->table_name, $row->record_id])),
        ];
    }

    private static function dataset(string $key, string $label, string $permission, array $filters, array $headers, callable $rows): array
    {
        return compact('key', 'label', 'permission', 'filters', 'headers', 'rows');
    }

    private static function query(Request $request, $query, ?string $categoryColumn = null, ?string $dateColumn = null)
    {
        if ($categoryColumn && $request->filled('category_id')) {
            $query->where($categoryColumn, $request->integer('category_id'));
        }

        if ($dateColumn) {
            $query->when($request->filled('start_date'), fn ($q) => $q->whereDate($dateColumn, '>=', $request->input('start_date')));
            $query->when($request->filled('end_date'), fn ($q) => $q->whereDate($dateColumn, '<=', $request->input('end_date')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }
}
