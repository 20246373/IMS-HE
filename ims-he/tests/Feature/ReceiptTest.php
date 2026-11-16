<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// UC11 / Receipt Printer interface (SDD Section 6): the receipt must show the
// transaction number, products, quantities, total, payment and change.
class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_receipt_shows_all_required_fields(): void
    {
        $category = Category::create(['category_name' => 'Hardware']);
        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Claw Hammer 16oz',
            'unit_price' => 350,
            'stock_quantity' => 10,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $cashier = User::create([
            'username' => 'cashier1', 'email' => 'cashier1@example.com',
            'password' => 'secret123', 'role' => User::EMPLOYEE, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($cashier)->post('/sales', [
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'amount_paid' => 1000,
            'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();

        $this->actingAs($cashier)->get("/sales/{$invoice->invoice_id}")
            ->assertOk()
            ->assertSee('Ibay Enterprises Corporation')
            ->assertSee('Transaction no')
            ->assertSee('#'.$invoice->invoice_id)
            ->assertSee('Claw Hammer 16oz')
            ->assertSee('700.00')          // total: 2 x 350
            ->assertSee('Cash')            // payment method
            ->assertSee('300.00')          // change: 1000 - 700
            ->assertSee('Print receipt');  // printable view
    }
}
