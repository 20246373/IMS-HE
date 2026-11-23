<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Administrator account is strictly for account management (Employee and Customer Accounts, Roles,
 * Audit Log), so it no longer has the Export Center. The Administrator role cannot be edited on the
 * Roles page, so the change is made here.
 */
return new class extends Migration
{
    private const EXPORTS = ['exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers'];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Administrator')->value('role_id');

        if ($roleId) {
            DB::table('role_permissions')->where('role_id', $roleId)->whereIn('permission', self::EXPORTS)->delete();
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Administrator')->value('role_id');

        if ($roleId) {
            DB::table('role_permissions')->insertOrIgnore(
                array_map(fn (string $permission) => ['role_id' => $roleId, 'permission' => $permission], self::EXPORTS)
            );
        }
    }
};
