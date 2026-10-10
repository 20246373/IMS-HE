<?php

namespace Tests\Feature\Procurement;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::INVENTORY_CLERK]);
        $this->actingAs($this->user);
    }

    public function test_create_supplier(): void
    {
        $response = $this->post(route('suppliers.store'), [
            'name' => 'Test Supplier',
            'contact_number' => '9123456789',
            'address' => '123 Test Street',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Supplier',
            'contact_number' => '9123456789',
            'address' => '123 Test Street',
        ]);
    }

    public function test_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $response = $this->put(route('suppliers.update', $supplier), [
            'name' => 'Updated Supplier',
            'contact_number' => '9988776655',
            'address' => '456 Updated Street',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
            'contact_number' => '9988776655',
            'address' => '456 Updated Street',
        ]);
    }

    public function test_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $response = $this->delete(route('suppliers.destroy', $supplier));

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_validation_fails_on_missing_required_fields(): void
    {
        $response = $this->post(route('suppliers.store'), [
            'name' => '',
            'contact_number' => '',
            'address' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'contact_number', 'address']);
    }

    public function test_delete_supplier_with_purchase_orders_is_blocked(): void
    {
        $supplier = Supplier::factory()->hasPurchaseOrders()->create();
        $response = $this->delete(route('suppliers.destroy', $supplier));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['supplier']);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_disallowed_role_gets_403(): void
    {
        $employee = User::factory()->create(['role' => User::EMPLOYEE]);
        $this->actingAs($employee);

        $response = $this->post(route('suppliers.store'), [
            'name' => 'Test Supplier',
            'contact_number' => '9123456789',
            'address' => '123 Test Street',
        ]);

        $response->assertStatus(403);
    }
}
