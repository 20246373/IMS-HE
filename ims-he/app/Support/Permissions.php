<?php

namespace App\Support;

use App\Models\User;

/**
 * Everything a role can be allowed to do. Routes use the keys (middleware "permission:key"),
 * and the Roles form lists them grouped by area. The Administrator is a fixed system role:
 * it always manages accounts, roles and the audit log, which are not listed here.
 */
class Permissions
{
    /** @var array<string, array<string, string>> group label => [key => label] */
    public const GROUPS = [
        'Sales and orders' => [
            'sales.pos' => 'Sales (POS)',
            'sales.view_all' => "See every cashier's sales",
            'online_orders.manage' => 'Online orders',
            'customers.manage' => 'Customers',
        ],
        'Inventory' => [
            'inventory.manage' => 'Products, categories and stock',
            'suppliers.manage' => 'Add and edit suppliers',
            'suppliers.delete' => 'Delete suppliers',
            'procurement.manage' => 'Purchase orders and deliveries',
        ],
        'People' => [
            'attendance.own' => 'Clock in and out (own attendance)',
            'attendance.manage' => 'Daily attendance and periods',
            'employees.manage' => 'Employees',
            'payroll.view' => 'View payroll',
            'payroll.run' => 'Run, finalize and delete payroll',
        ],
        'Finance' => [
            'ledger.view' => 'View the ledger',
            'ledger.record' => 'Record and reverse ledger entries',
        ],
        'Reports' => [
            'reports.sales' => 'Sales report',
            'reports.inventory' => 'Inventory report',
            'reports.procurement' => 'Procurement report',
            'reports.financial' => 'Financial reports',
        ],
        'Export Center' => [
            'exports.inventory' => 'Export inventory and purchasing data',
            'exports.suppliers' => 'Export suppliers',
            'exports.sales' => 'Export sales and payments',
            'exports.payroll' => 'Export ledger, employees and payroll',
            'exports.customers' => 'Export customers',
        ],
    ];

    /** What each built-in role could do before roles became editable (same access as the old hardcoded checks). */
    public const DEFAULTS = [
        User::ADMINISTRATOR => ['exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers'],
        User::PRESIDENT => [
            'online_orders.manage', 'customers.manage', 'inventory.manage', 'suppliers.manage', 'suppliers.delete', 'procurement.manage',
            'employees.manage', 'payroll.view', 'ledger.view', 'reports.sales', 'reports.inventory', 'reports.procurement', 'reports.financial',
            'exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers',
        ],
        User::TREASURER => [
            'attendance.manage', 'employees.manage', 'payroll.view', 'payroll.run', 'ledger.view', 'ledger.record',
            'reports.sales', 'reports.financial', 'exports.sales', 'exports.payroll',
        ],
        User::INVENTORY_CLERK => [
            'inventory.manage', 'suppliers.manage', 'procurement.manage', 'reports.inventory', 'reports.procurement', 'exports.inventory',
        ],
        User::STORE_SUPERVISOR => [
            'sales.pos', 'sales.view_all', 'online_orders.manage', 'customers.manage', 'inventory.manage', 'suppliers.manage',
            'procurement.manage', 'attendance.manage', 'ledger.view', 'reports.sales', 'reports.inventory',
            'exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.customers',
        ],
        User::EMPLOYEE => ['sales.pos', 'online_orders.manage', 'attendance.own'],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::GROUPS)));
    }

    public static function label(string $key): string
    {
        foreach (self::GROUPS as $permissions) {
            if (isset($permissions[$key])) {
                return $permissions[$key];
            }
        }

        return $key;
    }
}
