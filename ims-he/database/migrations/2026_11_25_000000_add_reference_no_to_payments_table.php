<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The reference number on the customer's GCash receipt, so staff can match a payment to the actual transfer.
 * Cash payments leave it empty. Unique, so the same GCash transfer cannot be used to pay for two orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_no', 30)->nullable()->unique()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['reference_no']);
            $table->dropColumn('reference_no');
        });
    }
};
