<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'email' => "customer-manager-{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'email' => 'maria@example.com',
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'contact_number' => '09171234567',
            'address' => 'Baguio City',
            'password' => 'do-not-display',
        ], $overrides));
    }

    public function test_staff_can_search_customers_by_name_or_email(): void
    {
        $customer = $this->customer();
        $this->customer(['email' => 'other@example.com', 'first_name' => 'Pedro']);

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get(route('customers.index', ['q' => 'Maria']))
            ->assertOk()
            ->assertSee($customer->email)
            ->assertSee('09171234567')
            ->assertDontSee('other@example.com');
    }

    public function test_customer_profile_shows_orders_and_online_order_link_without_password(): void
    {
        $customer = $this->customer();
        $online = Invoice::create([
            'invoice_date' => '2026-11-01',
            'customer_id' => $customer->customer_id,
            'channel' => 'Online',
            'status' => 'Completed',
            'total_amount' => 1234.50,
        ]);
        Invoice::create([
            'invoice_date' => '2026-11-02',
            'customer_id' => $customer->customer_id,
            'channel' => 'In-store',
            'status' => 'Completed',
            'total_amount' => 500,
        ]);

        $this->actingAs($this->staff(User::PRESIDENT))
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('#'.$online->invoice_id)
            ->assertSee('₱1,234.50')
            ->assertSee('Open online order')
            ->assertDontSee('do-not-display');
    }

    public function test_customer_update_changes_email_validates_digits_and_audits(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee($customer->email)
            ->assertDontSee('do-not-display');

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->put(route('customers.update', $customer), [
                'email' => 'changed@example.com',
                'first_name' => 'Updated',
                'last_name' => 'Customer',
                'contact_number' => '09181234567',
                'address' => 'New address',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $updated = $customer->fresh();
        $this->assertSame('changed@example.com', $updated->email);
        $this->assertSame('Updated', $updated->first_name);
        $this->assertSame('09181234567', $updated->contact_number);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Updated',
            'table_name' => 'customers',
            'record_id' => $customer->customer_id,
        ]);

        $this->actingAs($this->staff(User::PRESIDENT))
            ->put(route('customers.update', $customer), ['email' => 'changed@example.com', 'contact_number' => 'not-digits'])
            ->assertSessionHasErrors('contact_number');

        // The email must stay unique across customers and staff.
        $this->actingAs($this->staff(User::PRESIDENT))
            ->put(route('customers.update', $customer), ['email' => User::first()->email])
            ->assertSessionHasErrors('email');
    }

    public function test_customer_delete_redirects_audits_and_preserves_orders(): void
    {
        $customer = $this->customer();
        $order = Invoice::create([
            'invoice_date' => '2026-11-18',
            'customer_id' => $customer->customer_id,
            'channel' => 'In-store',
            'status' => 'Completed',
            'total_amount' => 500,
        ]);

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('customers', ['customer_id' => $customer->customer_id]);
        $this->assertDatabaseHas('invoices', [
            'invoice_id' => $order->invoice_id,
            'customer_id' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Deleted',
            'table_name' => 'customers',
            'record_id' => $customer->customer_id,
        ]);
    }

    public function test_authorized_roles_can_manage_customers(): void
    {
        $customer = $this->customer();

        foreach ([User::PRESIDENT, User::STORE_SUPERVISOR] as $role) {
            $this->actingAs($this->staff($role))
                ->get(route('customers.index'))
                ->assertOk();
        }

        foreach ([User::ADMINISTRATOR, User::EMPLOYEE, User::TREASURER, User::INVENTORY_CLERK] as $role) {
            $this->actingAs($this->staff($role))
                ->get(route('customers.index'))
                ->assertForbidden();
        }

        $this->assertModelExists($customer);
    }
}
