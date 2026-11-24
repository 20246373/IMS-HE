<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customers' own actions (signing up, logging in, placing orders, ...) are audited too.
 * user_id is the employee who acted, customer_id the customer who acted; both empty = the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')->nullable()->after('user_id');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
