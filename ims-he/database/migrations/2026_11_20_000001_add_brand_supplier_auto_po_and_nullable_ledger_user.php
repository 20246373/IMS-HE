<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('brand', 50)->nullable()->after('product_name');
            $table->unsignedInteger('supplier_id')->nullable()->after('brand');

            $table->foreign('supplier_id')
                ->references('supplier_id')
                ->on('suppliers')
                ->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->boolean('is_auto')->default(false);
        });

        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['recorded_by']);
            $table->unsignedInteger('recorded_by')->nullable()->change();

            $table->foreign('recorded_by')
                ->references('user_id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['recorded_by']);
            $table->unsignedInteger('recorded_by')->nullable(false)->change();

            $table->foreign('recorded_by')
                ->references('user_id')
                ->on('users');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn('is_auto');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'brand']);
        });
    }
};
