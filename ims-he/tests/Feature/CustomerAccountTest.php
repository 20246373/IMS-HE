<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// The Administrator manages customers' storefront logins on the Customer Accounts page.
class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'email' => "account-admin-{$number}@example.com",
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
            'password' => 'secret123',
        ], $overrides));
    }

    public function test_administrator_lists_and_searches_customer_accounts(): void
    {
        $this->customer();
        $this->customer(['email' => 'pedro@example.com', 'first_name' => 'Pedro']);

        $this->actingAs($this->staff(User::ADMINISTRATOR))
            ->get(route('customer-accounts.index', ['q' => 'Maria']))
            ->assertOk()
            ->assertSee('maria@example.com')
            ->assertSee('Maria Cruz')
            ->assertDontSee('pedro@example.com');
    }

    public function test_administrator_edits_verifies_and_resets_the_password(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $customer = $this->customer();

        $this->actingAs($administrator)->put(route('customer-accounts.update', $customer), [
            'email' => 'New@Example.com', 'first_name' => 'Mara', 'last_name' => 'Cruz',
        ])->assertRedirect(route('customer-accounts.index'));
        $this->assertSame('new@example.com', $customer->fresh()->email);
        $this->assertSame('Mara', $customer->fresh()->first_name);

        // The email stays unique across staff and customers.
        $this->actingAs($administrator)->put(route('customer-accounts.update', $customer), ['email' => $administrator->email])
            ->assertSessionHasErrors('email');

        $this->actingAs($administrator)->post(route('customer-accounts.verify', $customer))->assertSessionHas('status');
        $this->assertTrue($customer->fresh()->hasVerifiedEmail());

        $this->actingAs($administrator)->post(route('customer-accounts.password', $customer), [
            'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('newpass123', $customer->fresh()->password));

        $this->assertDatabaseHas('audit_logs', ['action' => 'Password Reset', 'table_name' => 'customers', 'record_id' => $customer->customer_id]);
    }

    public function test_deleting_an_account_keeps_past_orders(): void
    {
        $customer = $this->customer();
        $order = Invoice::create([
            'invoice_date' => '2026-11-18', 'customer_id' => $customer->customer_id,
            'channel' => 'Online', 'status' => 'Completed', 'total_amount' => 500,
        ]);

        $this->actingAs($this->staff(User::ADMINISTRATOR))
            ->delete(route('customer-accounts.destroy', $customer))
            ->assertRedirect(route('customer-accounts.index'));

        $this->assertModelMissing($customer);
        $this->assertDatabaseHas('invoices', ['invoice_id' => $order->invoice_id, 'customer_id' => null]);
    }

    public function test_only_the_administrator_can_open_customer_accounts(): void
    {
        $customer = $this->customer();

        foreach ([User::PRESIDENT, User::STORE_SUPERVISOR, User::EMPLOYEE] as $role) {
            $this->actingAs($this->staff($role))->get(route('customer-accounts.index'))->assertForbidden();
        }

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->delete(route('customer-accounts.destroy', $customer))->assertForbidden();
        $this->assertModelExists($customer);
    }
}
