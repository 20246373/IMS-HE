<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who did it
            $t->string('action', 50);                     // e.g. account.created
            $t->string('target_type', 50)->nullable();    // e.g. User
            $t->string('target_id', 30)->nullable();
            $t->text('details')->nullable();
            $t->timestamps();
        });

        // One cart per customer (unique customer_id), one line per product (unique cart+product).
        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $t->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->timestamps();
            $t->unique(['cart_id', 'product_id']);
        });

        Schema::table('products', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('reorder_point'); // hide instead of delete
        });
        Schema::table('invoices', function (Blueprint $t) {
            $t->string('channel', 20)->default('In-store')->after('user_id'); // In-store | Online
        });
        Schema::table('purchase_order_items', function (Blueprint $t) {
            $t->unsignedInteger('quantity_received')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', fn (Blueprint $t) => $t->dropColumn('quantity_received'));
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('channel'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('audit_logs');
    }
};
