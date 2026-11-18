<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles become editable by the Administrator. users.role still holds the role name, so existing accounts keep working.
 * The built-in roles are created with exactly the access they had when it was hardcoded.
 */
return new class extends Migration
{
    /** The permissions each built-in role starts with (a copy, so later code changes cannot alter this migration). */
    private const DEFAULTS = [
        'Administrator' => ['exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers'],
        'President' => [
            'online_orders.manage', 'customers.manage', 'inventory.manage', 'suppliers.manage', 'suppliers.delete', 'procurement.manage',
            'employees.manage', 'payroll.view', 'ledger.view', 'reports.sales', 'reports.inventory', 'reports.procurement', 'reports.financial',
            'exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers',
        ],
        'Treasurer' => [
            'attendance.manage', 'employees.manage', 'payroll.view', 'payroll.run', 'ledger.view', 'ledger.record',
            'reports.sales', 'reports.financial', 'exports.sales', 'exports.payroll',
        ],
        'Inventory Clerk' => [
            'inventory.manage', 'suppliers.manage', 'procurement.manage', 'reports.inventory', 'reports.procurement', 'exports.inventory',
        ],
        'Store Supervisor' => [
            'sales.pos', 'sales.view_all', 'online_orders.manage', 'customers.manage', 'inventory.manage', 'suppliers.manage',
            'procurement.manage', 'attendance.manage', 'ledger.view', 'reports.sales', 'reports.inventory',
            'exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.customers',
        ],
        'Employee' => ['sales.pos', 'online_orders.manage', 'attendance.own'],
    ];

    private const DESCRIPTIONS = [
        'Administrator' => 'Manages user accounts, roles and the audit log.',
        'President' => 'Oversees the whole business.',
        'Treasurer' => 'Handles payroll, the ledger and financial reports.',
        'Inventory Clerk' => 'Keeps products, stock and purchasing up to date.',
        'Store Supervisor' => 'Runs the store floor, sales and attendance.',
        'Employee' => 'Works the counter and handles online orders.',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->increments('role_id');
            $t->string('role_name', 50)->unique();
            $t->string('description', 255)->nullable();
            $t->boolean('is_system')->default(false);   // Administrator: cannot be renamed, deleted or edited
            $t->dateTime('created_at')->useCurrent();
        });

        Schema::create('role_permissions', function (Blueprint $t) {
            $t->unsignedInteger('role_id');
            $t->string('permission', 50);
            $t->primary(['role_id', 'permission']);
            $t->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
        });

        foreach (self::DEFAULTS as $name => $permissions) {
            $roleId = DB::table('roles')->insertGetId([
                'role_name' => $name,
                'description' => self::DESCRIPTIONS[$name],
                'is_system' => $name === 'Administrator',
            ], 'role_id');

            DB::table('role_permissions')->insert(array_map(fn ($p) => ['role_id' => $roleId, 'permission' => $p], $permissions));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
