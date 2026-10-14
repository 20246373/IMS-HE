<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IMS-HE schema - SDD Section 7.2 (19 tables), created in foreign-key order.
 * Note: SDD 7.2.7 typo "quanity_received" is corrected to quantity_received here.
 */
return new class extends Migration {
    public function up(): void
    {
        // 7.2.8
        Schema::create('employees', function (Blueprint $t) {
            $t->increments('employee_id');
            $t->string('first_name', 50);
            $t->string('last_name', 50);
            $t->string('position', 50);
            $t->decimal('daily_rate', 10, 2)->default(0);
            $t->string('sss_no', 20)->nullable();
            $t->string('philhealth_no', 20)->nullable();
            $t->string('pagibig_no', 20)->nullable();
            $t->string('tin_no', 20)->nullable();
            $t->date('date_hired')->nullable();
            $t->string('status', 20)->default('Active');          // Active | Resigned
        });

        // 7.2.1
        Schema::create('users', function (Blueprint $t) {
            $t->increments('user_id');
            $t->unsignedInteger('employee_id')->nullable()->unique();
            $t->string('username', 50)->unique();
            $t->string('password', 255);                          // bcrypt
            $t->string('role', 50);                               // President | Treasurer | Inventory Clerk | Store Supervisor | Employee
            $t->string('status', 10)->default('Active');          // Active | Locked
            $t->unsignedInteger('login_attempt_count')->default(0);
            $t->dateTime('last_login')->nullable();
            $t->dateTime('created_at')->useCurrent();

            $t->foreign('employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // 7.2.2
        Schema::create('customers', function (Blueprint $t) {
            $t->increments('customer_id');
            $t->string('first_name', 50);
            $t->string('last_name', 50);
            $t->string('username', 50)->unique();
            $t->string('email', 100)->unique();
            $t->dateTime('email_verified_at')->nullable();
            $t->string('contact_number', 14);
            $t->string('address', 100);
            $t->string('password', 255);
            $t->dateTime('created_at')->useCurrent();
        });

        // 7.2.3
        Schema::create('categories', function (Blueprint $t) {
            $t->increments('category_id');
            $t->string('category_name', 50)->unique();
        });

        // 7.2.4
        Schema::create('products', function (Blueprint $t) {
            $t->increments('product_id');
            $t->unsignedInteger('category_id');
            $t->string('product_name', 100)->unique();
            $t->string('description', 255)->nullable();
            $t->decimal('unit_price', 10, 2)->default(0);
            $t->unsignedInteger('stock_quantity')->default(0);
            $t->unsignedInteger('reorder_level')->default(0);
            $t->string('image_path', 255)->nullable();
            $t->boolean('is_active')->default(true);

            $t->foreign('category_id')->references('category_id')->on('categories');
        });

        // 7.2.5
        Schema::create('suppliers', function (Blueprint $t) {
            $t->increments('supplier_id');
            $t->string('supplier_name', 100)->unique();
            $t->string('contact_number', 14);
            $t->string('address', 100);
        });

        // 7.2.6
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->increments('po_id');
            $t->unsignedInteger('supplier_id');
            $t->date('order_date');
            $t->string('status', 20)->default('Pending');         // Pending | Delivered | Cancelled

            $t->foreign('supplier_id')->references('supplier_id')->on('suppliers');
        });

        // 7.2.7
        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->increments('po_item_id');
            $t->unsignedInteger('po_id');
            $t->unsignedInteger('product_id');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('quantity_received')->default(0);
            $t->decimal('unit_cost', 10, 2);
            $t->decimal('subtotal', 10, 2);

            $t->foreign('po_id')->references('po_id')->on('purchase_orders')->cascadeOnDelete();
            $t->foreign('product_id')->references('product_id')->on('products');
        });

        // 7.2.9
        Schema::create('attendance', function (Blueprint $t) {
            $t->increments('attendance_id');
            $t->unsignedInteger('employee_id');
            $t->date('work_date');
            $t->time('time_in');
            $t->time('time_out')->nullable();
            $t->decimal('overtime_hours', 5, 2)->default(0);

            $t->unique(['employee_id', 'work_date']);
            $t->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });

        // 7.2.10
        Schema::create('payrolls', function (Blueprint $t) {
            $t->increments('payroll_id');
            $t->date('period_start');
            $t->date('period_end');
            $t->unsignedInteger('processed_by');
            $t->dateTime('processed_date')->useCurrent();
            $t->string('status', 20)->default('Draft');           // Draft | Final
            $t->decimal('total_gross', 12, 2)->default(0);
            $t->decimal('total_deductions', 12, 2)->default(0);
            $t->decimal('total_net', 12, 2)->default(0);

            $t->foreign('processed_by')->references('user_id')->on('users');
        });

        // 7.2.11
        Schema::create('payslips', function (Blueprint $t) {
            $t->increments('payslip_id');
            $t->unsignedInteger('payroll_id');
            $t->unsignedInteger('employee_id');
            $t->decimal('days_worked', 5, 2)->default(0);
            $t->decimal('overtime_hours', 5, 2)->default(0);
            $t->decimal('gross_pay', 10, 2)->default(0);
            $t->decimal('sss_deduction', 10, 2)->default(0);
            $t->decimal('philhealth_deduction', 10, 2)->default(0);
            $t->decimal('pagibig_deduction', 10, 2)->default(0);
            $t->decimal('tax_deduction', 10, 2)->default(0);
            $t->decimal('other_deductions', 10, 2)->default(0);
            $t->decimal('total_deductions', 10, 2)->default(0);
            $t->decimal('net_pay', 10, 2)->default(0);

            $t->foreign('payroll_id')->references('payroll_id')->on('payrolls')->cascadeOnDelete();
            $t->foreign('employee_id')->references('employee_id')->on('employees');
        });

        // 7.2.12
        Schema::create('invoices', function (Blueprint $t) {
            $t->increments('invoice_id');
            $t->date('invoice_date');
            $t->unsignedInteger('customer_id')->nullable();       // null = walk-in
            $t->unsignedInteger('employee_id')->nullable();       // null = online order
            $t->string('channel', 10);                            // In-store | Online
            $t->string('status', 20)->default('Pending');         // Pending | Completed | Cancelled
            $t->decimal('total_amount', 10, 2)->default(0);

            $t->foreign('customer_id')->references('customer_id')->on('customers')->nullOnDelete();
            $t->foreign('employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // 7.2.13 (ERD name: invoice_items)
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->increments('invoice_item_id');
            $t->unsignedInteger('invoice_id');
            $t->unsignedInteger('product_id');
            $t->unsignedInteger('quantity');
            $t->decimal('price', 10, 2);                          // unit price at time of sale
            $t->decimal('subtotal', 10, 2);

            $t->foreign('invoice_id')->references('invoice_id')->on('invoices')->cascadeOnDelete();
            $t->foreign('product_id')->references('product_id')->on('products');
        });

        // 7.2.14
        Schema::create('payments', function (Blueprint $t) {
            $t->increments('payment_id');
            $t->unsignedInteger('invoice_id');
            $t->date('payment_date');
            $t->decimal('amount_paid', 10, 2);
            $t->decimal('change_amount', 10, 2)->default(0);
            $t->string('payment_method', 20);                     // Cash, GCash

            $t->foreign('invoice_id')->references('invoice_id')->on('invoices')->cascadeOnDelete();
        });

        // 7.2.15
        Schema::create('inventory_transactions', function (Blueprint $t) {
            $t->increments('transaction_id');
            $t->unsignedInteger('product_id');
            $t->string('transaction_type', 30);                   // IN | OUT
            $t->unsignedInteger('quantity');
            $t->date('transaction_date');
            $t->string('reference_type', 30);                     // Invoice | PO
            $t->unsignedInteger('reference_id');

            $t->foreign('product_id')->references('product_id')->on('products');
        });

        // 7.2.16
        Schema::create('carts', function (Blueprint $t) {
            $t->increments('cart_id');
            $t->unsignedInteger('customer_id')->unique();
            $t->dateTime('updated_at')->useCurrent();

            $t->foreign('customer_id')->references('customer_id')->on('customers')->cascadeOnDelete();
        });

        // 7.2.17
        Schema::create('cart_items', function (Blueprint $t) {
            $t->increments('cart_item_id');
            $t->unsignedInteger('cart_id');
            $t->unsignedInteger('product_id');
            $t->unsignedInteger('quantity')->default(1);

            $t->unique(['cart_id', 'product_id']);
            $t->foreign('cart_id')->references('cart_id')->on('carts')->cascadeOnDelete();
            $t->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
        });

        // 7.2.18
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->increments('ledger_id');
            $t->date('entry_date');
            $t->string('entry_type', 10);                         // Income | Expense
            $t->decimal('amount', 12, 2);
            $t->string('description', 255)->nullable();
            $t->string('reference_type', 30)->nullable();         // Invoice | PO | Payroll
            $t->unsignedInteger('reference_id')->nullable();
            $t->unsignedInteger('recorded_by');

            $t->foreign('recorded_by')->references('user_id')->on('users');
        });

        // 7.2.19
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->increments('log_id');
            $t->unsignedInteger('user_id')->nullable();
            $t->string('action', 100);
            $t->string('table_name', 50);
            $t->unsignedInteger('record_id')->nullable();
            $t->dateTime('logged_at')->useCurrent();

            $t->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'ledger_entries', 'cart_items', 'carts', 'inventory_transactions', 'payments',
            'invoice_items', 'invoices', 'payslips', 'payrolls', 'attendance', 'purchase_order_items',
            'purchase_orders', 'suppliers', 'products', 'categories', 'customers', 'users', 'employees',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
