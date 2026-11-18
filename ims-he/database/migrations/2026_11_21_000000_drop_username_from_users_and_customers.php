<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts are identified by email only (emails are unique), so the optional username columns go away.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'customers'] as $tableName) {
            if (Schema::hasColumn($tableName, 'username')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropUnique(['username']);
                    $table->dropColumn('username');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'customers'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'username')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('username', 50)->nullable()->unique()->after('email');
                });
            }
        }
    }
};
