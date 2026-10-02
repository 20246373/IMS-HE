<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SRS 5.2.a DDT-Login (+ customers share this table, role = customer)
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username', 50)->unique();
            $t->string('name', 100);
            $t->string('email')->nullable()->unique();
            $t->string('password');                       // bcrypt hash
            $t->string('role', 50);                       // see App\Models\User::ROLES
            $t->unsignedInteger('login_attempt_count')->default(0);
            $t->boolean('is_locked')->default(false);     // locks after 3 failed attempts
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        // SRS 5.2.b DDT-Customer Registration (profile; credentials live in users)
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('first_name', 50);
            $t->string('last_name', 50);
            $t->string('contact_number', 14);
            $t->string('address', 100);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();
            $t->timestamps();
        });

        // SRS 5.2.f DDT-Product Management (+ brand/category/description/reorder_point from Product Functions)
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name', 100)->unique();
            $t->string('brand', 100)->nullable();
            $t->text('description')->nullable();
            $t->string('image_path')->nullable();
            $t->decimal('unit_price', 10, 2)->default(0);
            $t->unsignedInteger('stock_quantity')->default(0);
            $t->unsignedInteger('reorder_point')->default(0);
            $t->timestamps();
        });

        // SRS 5.2.g DDT-Inventory Transaction
        Schema::create('inventory_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->string('type', 30);                       // IN | OUT
            $t->unsignedInteger('quantity');
            $t->date('transaction_date');
            $t->string('reference_type', 30);             // Invoice | PO | Adjustment
            $t->string('reference_id', 30)->nullable();
            $t->timestamps();
        });

        // SRS 5.2.h DDT-Manage Supplier
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();
            $t->string('contact_number', 14);
            $t->string('address', 100);
            $t->timestamps();
        });

        // SRS 5.2.i / 5.2.j DDT-Purchase Order (+ Item)
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supplier_id')->constrained();
            $t->date('order_date');
            $t->string('status', 20)->default('Pending'); // Pending | Delivered | Cancelled
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_cost', 10, 2);
            $t->decimal('subtotal', 10, 2);
            $t->timestamps();
        });

        // SRS 5.2.c DDT-Sales Transaction (invoice)
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->date('invoice_date');
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // associate who processed it
            $t->decimal('total_amount', 10, 2)->default(0);
            $t->timestamps();
        });
        // SRS 5.2.d DDT-Invoice Item
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('price', 10, 2);                  // unit price at time of sale
            $t->decimal('subtotal', 10, 2);
            $t->timestamps();
        });
        // SRS 5.2.e DDT-Payment
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->date('payment_date');
            $t->decimal('amount_paid', 10, 2);
            $t->decimal('change_amount', 10, 2)->default(0);
            $t->string('payment_method', 20);             // Cash, GCash
            $t->timestamps();
        });

        // SRS 5.2.k DDT-Employee Payroll (employee master)
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('first_name', 50);
            $t->string('last_name', 50);
            $t->string('position', 50);
            $t->decimal('daily_rate', 10, 2)->default(0);
            $t->string('sss_no', 20)->nullable();
            $t->string('philhealth_no', 20)->nullable();
            $t->string('pagibig_no', 20)->nullable();
            $t->string('tin_no', 20)->nullable();
            $t->date('date_hired')->nullable();
            $t->string('status', 20)->default('Active');  // Active | Resigned
            $t->timestamps();
        });
        // Attendance (SDD 4.1 Payroll module; SRS Product Functions)
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('attendance_date');
            $t->string('status', 20);                     // Present | Absent | Half-day | Leave
            $t->decimal('hours_worked', 5, 2)->default(0);
            $t->decimal('overtime_hours', 5, 2)->default(0);
            $t->timestamps();
            $t->unique(['employee_id', 'attendance_date']);
        });
        // SRS 5.2.l DDT-Payroll
        Schema::create('payrolls', function (Blueprint $t) {
            $t->id();
            $t->date('period_start');
            $t->date('period_end');
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('processed_at')->useCurrent();
            $t->string('status', 20)->default('Draft');   // Draft | Final
            $t->decimal('total_gross', 12, 2)->default(0);
            $t->decimal('total_deductions', 12, 2)->default(0);
            $t->decimal('total_net', 12, 2)->default(0);
            $t->timestamps();
        });
        // SRS 5.2.m DDT-Payslip
        Schema::create('payslips', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained();
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
            $t->timestamps();
        });

        // SDD 4.1 Financial Recording Module (income/expense ledger)
        Schema::create('financial_entries', function (Blueprint $t) {
            $t->id();
            $t->date('entry_date');
            $t->string('type', 10);                       // income | expense
            $t->decimal('amount', 12, 2);
            $t->string('description', 150);
            $t->string('reference_type', 30)->nullable();
            $t->string('reference_id', 30)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'financial_entries', 'payslips', 'payrolls', 'attendances', 'employees', 'payments',
            'invoice_items', 'invoices', 'purchase_order_items', 'purchase_orders', 'suppliers',
            'inventory_transactions', 'products', 'categories', 'customers', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
