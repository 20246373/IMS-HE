<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// "Editable values" rule: immutable identifiers and stock stay untouched, and purchase
// orders only move from Pending to Cancelled (no edit/update screen or route).
class EditableValuesTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, string $username = 'editor'): User
    {
        return User::create([
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function supplier(): Supplier
    {
        return Supplier::create([
            'supplier_name' => 'Sample Supply',
            'contact_number' => '09170000000',
            'address' => 'Baguio City',
        ]);
    }

    public function test_supplier_update_ignores_a_tampered_supplier_name(): void
    {
        $supplier = $this->supplier();
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)->put(route('suppliers.update', $supplier), [
            'supplier_name' => 'Renamed Supply',
            'contact_number' => '09998887777',
            'address' => 'La Trinidad',
        ])->assertRedirect(route('suppliers.index'))->assertSessionHasNoErrors();

        $supplier->refresh();
        $this->assertSame('Sample Supply', $supplier->supplier_name);
        $this->assertSame('09998887777', $supplier->contact_number);
        $this->assertSame('La Trinidad', $supplier->address);
    }

    public function test_purchase_order_edit_and_update_routes_do_not_exist(): void
    {
        $this->assertFalse(Route::has('purchase-orders.edit'));
        $this->assertFalse(Route::has('purchase-orders.update'));
    }

    public function test_pending_purchase_order_can_be_cancelled(): void
    {
        $supplier = $this->supplier();
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Pending',
        ]);
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)
            ->patch(route('purchase-orders.cancel', $purchaseOrder))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder))
            ->assertSessionHas('status', 'Purchase order cancelled.');

        $this->assertSame('Cancelled', $purchaseOrder->fresh()->status);
    }

    public function test_only_pending_purchase_orders_can_be_cancelled(): void
    {
        $supplier = $this->supplier();
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'order_date' => today(),
            'status' => 'Delivered',
        ]);
        $clerk = $this->staff(User::INVENTORY_CLERK);

        $this->actingAs($clerk)
            ->patch(route('purchase-orders.cancel', $purchaseOrder))
            ->assertSessionHasErrors('purchase_order');

        $this->assertSame('Delivered', $purchaseOrder->fresh()->status);
    }
}
