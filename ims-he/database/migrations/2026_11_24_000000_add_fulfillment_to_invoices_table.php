<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How an online order reaches the customer: picked up at the store or delivered.
 * In-store (POS) sales leave it empty, since the customer is already at the counter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('fulfillment', 10)->nullable()->after('channel');
        });

        // Online orders placed before this change always asked for an address, so they were deliveries.
        DB::table('invoices')->where('channel', 'Online')->update(['fulfillment' => 'Delivery']);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('fulfillment');
        });
    }
};
