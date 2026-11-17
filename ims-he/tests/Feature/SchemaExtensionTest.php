<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_ledger_entries_and_purchase_orders_support_new_fields(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Acme Supply',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
        $category = Category::create(['category_name' => 'Test Category']);

        $product = Product::create([
            'product_name' => 'Test Product',
            'category_id' => $category->category_id,
            'brand' => 'Acme',
            'supplier_id' => $supplier->supplier_id,
        ]);

        $ledgerEntry = LedgerEntry::create([
            'entry_date' => today(),
            'entry_type' => 'Sale',
            'amount' => 100,
            'description' => 'Online order',
            'recorded_by' => null,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
        ]);

        $this->assertSame('Acme', $product->fresh()->brand);
        $this->assertSame($supplier->supplier_id, $product->fresh()->supplier_id);
        $this->assertNull($ledgerEntry->fresh()->recorded_by);
        $this->assertFalse($purchaseOrder->fresh()->is_auto);
    }
}
