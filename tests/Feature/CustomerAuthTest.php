<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'username' => 'juan123',
            'email' => 'juan@example.com',
            'contact_number' => '09171234567',
            'address' => 'Baguio City',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_customer_can_register_with_hashed_password(): void
    {
        $this->post('/register', $this->validData())->assertRedirect(route('customer.login'));

        $customer = Customer::where('username', 'juan123')->firstOrFail();
        $this->assertNotSame('secret123', $customer->password);
        $this->assertTrue(Hash::check('secret123', $customer->password));
        $this->assertNull($customer->email_verified_at);
        $this->assertGuest('customer');           // registration redirects to login (UC02 includes UC01)
    }

    public function test_register_rejects_invalid_fields(): void
    {
        $this->post('/register', $this->validData([
            'first_name' => 'Juan2',
            'username' => 'juan_123',
            'email' => 'not-an-email',
            'contact_number' => '0917-123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))->assertSessionHasErrors(['first_name', 'username', 'email', 'contact_number', 'password']);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_register_rejects_duplicate_username_and_email(): void
    {
        Customer::create(collect($this->validData())->except('password_confirmation')->all());

        $this->post('/register', $this->validData())->assertSessionHasErrors(['username', 'email']);
    }

    public function test_customer_can_log_in_with_username_or_email_on_customer_guard(): void
    {
        $customer = Customer::create(collect($this->validData())->except('password_confirmation')->all());

        $this->post('/login/customer', ['login' => 'juan123', 'password' => 'secret123'])
            ->assertRedirect(route('customer.account'));
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');                // customer login never touches the staff guard

        $this->post('/customer/logout');
        $this->assertGuest('customer');

        $this->post('/login/customer', ['login' => 'juan@example.com', 'password' => 'secret123']);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_wrong_password_is_rejected(): void
    {
        Customer::create(collect($this->validData())->except('password_confirmation')->all());

        $this->post('/login/customer', ['login' => 'juan123', 'password' => 'wrongpass'])
            ->assertSessionHasErrors('login');
        $this->assertGuest('customer');
    }

    public function test_login_page_offers_staff_and_customer_access(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('staff.login'))
            ->assertSee(route('customer.login'))
            ->assertSee(route('customer.register'));
    }

    public function test_account_page_requires_customer_login(): void
    {
        $this->get('/account')->assertRedirect(route('customer.login'));
    }
}
