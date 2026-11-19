<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff accounts carry the person's own name (entered at sign-up), so the sidebar and the
 * User Accounts page can show it even before the account is linked to an employee record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 50)->nullable()->after('employee_id');
            $table->string('last_name', 50)->nullable()->after('first_name');
        });

        // Existing accounts take the name of the employee they are linked to.
        foreach (DB::table('users')->whereNotNull('employee_id')->get(['user_id', 'employee_id']) as $user) {
            $employee = DB::table('employees')->where('employee_id', $user->employee_id)->first(['first_name', 'last_name']);

            if ($employee) {
                DB::table('users')->where('user_id', $user->user_id)
                    ->update(['first_name' => $employee->first_name, 'last_name' => $employee->last_name]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
