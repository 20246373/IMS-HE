<?php

namespace Tests\Feature;

use App\Mail\VerifyCustomerEmail;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// Customers sign up with only an email and a password, and log in on the shared /login page.
class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private function signup(array $overrides = []): array
    {
        return array_merge(['email' => 'juan@example.com', 'password' => 'secret123'], $overrides);
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge(['email' => 'juan@example.com', 'password' => 'secret123'], $overrides));
    }

    public function test_customer_signs_up_with_only_email_and_password(): void
    {
        Mail::fake();

        $this->post('/register', $this->signup())->assertRedirect(route('login'));

        $customer = Customer::where('email', 'juan@example.com')->firstOrFail();
        $this->assertNull($customer->first_name);
        $this->assertNull($customer->address);
        $this->assertNotSame('secret123', $customer->password);
        $this->assertTrue(Hash::check('secret123', $customer->password));
        $this->assertNull($customer->email_verified_at);
        $this->assertGuest('customer');
        Mail::assertSent(VerifyCustomerEmail::class, fn ($mail) => $mail->hasTo('juan@example.com'));
    }

    public function test_signup_rejects_a_bad_email_and_a_short_password(): void
    {
        $this->post('/register', $this->signup(['email' => 'not-an-email', 'password' => 'short']))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_signup_rejects_an_email_that_is_already_registered_in_any_case(): void
    {
        $this->customer();

        $this->post('/register', $this->signup(['email' => 'JUAN@Example.com']))
            ->assertSessionHasErrors(['email' => 'This email is already registered.']);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_signup_rejects_an_email_that_belongs_to_a_staff_account(): void
    {
        User::create(['email' => 'juan@example.com', 'password' => 'secret123', 'role' => User::EMPLOYEE, 'status' => User::STATUS_ACTIVE]);

        $this->post('/register', $this->signup())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_unverified_customer_cannot_log_in(): void
    {
        $this->customer();

        $this->post('/login', ['login' => 'juan@example.com', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest('customer');
    }

    public function test_verified_customer_logs_in_with_email_on_the_customer_guard(): void
    {
        $customer = $this->customer(['email_verified_at' => now()]);

        $this->post('/login', ['login' => 'Juan@Example.com', 'password' => 'secret123'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');                // a customer login never touches the staff guard

        $this->post('/customer/logout');
        $this->assertGuest('customer');
    }

    public function test_wrong_password_and_unknown_email_get_the_same_error(): void
    {
        $this->customer(['email_verified_at' => now()]);

        $this->post('/login', ['login' => 'juan@example.com', 'password' => 'wrongpass'])
            ->assertSessionHasErrors(['login' => 'Invalid email/username or password.']);
        $this->post('/login', ['login' => 'nobody@example.com', 'password' => 'secret123'])
            ->assertSessionHasErrors(['login' => 'Invalid email/username or password.']);
        $this->assertGuest('customer');
    }

    public function test_login_page_links_to_sign_up_and_employee_access(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('customer.register'))
            ->assertSee(route('staff.register'));
    }

    public function test_old_login_urls_redirect_to_the_shared_login(): void
    {
        $this->get('/login/customer')->assertRedirect('/login');
        $this->get('/login/staff')->assertRedirect('/login');
    }

    public function test_account_page_requires_customer_login(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
    }

    public function test_customer_can_complete_the_profile_later(): void
    {
        $customer = $this->customer(['email_verified_at' => now()]);

        $this->actingAs($customer, 'customer')->get('/account')->assertOk()->assertSee('juan@example.com');

        $this->actingAs($customer, 'customer')->patch(route('customer.account.update'), [
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'contact_number' => '09171234567', 'address' => 'Baguio City',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', ['customer_id' => $customer->customer_id, 'first_name' => 'Juan', 'address' => 'Baguio City']);

        $this->actingAs($customer, 'customer')->patch(route('customer.account.update'), ['contact_number' => '0917-123'])
            ->assertSessionHasErrors('contact_number');
    }

    public function test_display_name_falls_back_to_the_email_name(): void
    {
        $customer = $this->customer();
        $this->assertSame('juan', $customer->display_name);

        $customer->update(['first_name' => 'Juan']);
        $this->assertSame('Juan', $customer->fresh()->display_name);
    }

    public function test_signed_verification_link_verifies_the_email_and_signs_the_customer_in(): void
    {
        $customer = $this->customer();

        $this->get($customer->verificationUrl())->assertRedirect(route('home'));

        $this->assertNotNull($customer->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($customer->fresh(), 'customer');
    }

    public function test_the_link_only_signs_in_once(): void
    {
        $customer = $this->customer();
        $url = $customer->verificationUrl();

        $this->get($url);
        $this->post('/customer/logout');
        $this->assertGuest('customer');

        // Same link again: the email stays verified, but nobody is logged in by it.
        $this->get($url)->assertRedirect(route('login'));
        $this->assertGuest('customer');
    }

    public function test_unsigned_tampered_or_expired_links_are_rejected_with_a_clear_message(): void
    {
        $customer = $this->customer();
        $params = ['customer' => $customer->customer_id, 'hash' => sha1($customer->email)];

        // no signature
        $this->get(route('customer.verification.verify', $params))
            ->assertRedirect(route('login'))->assertSessionHasErrors('login');

        // valid signature, wrong hash
        $tampered = URL::temporarySignedRoute('customer.verification.verify', now()->addHour(), ['customer' => $customer->customer_id, 'hash' => 'wrong']);
        $this->get($tampered)->assertRedirect(route('login'))->assertSessionHasErrors('login');

        // expired
        $expired = URL::temporarySignedRoute('customer.verification.verify', now()->subMinute(), $params);
        $this->get($expired)->assertRedirect(route('login'))->assertSessionHasErrors('login');

        $this->assertNull($customer->fresh()->email_verified_at);
        $this->assertGuest('customer');
    }

    public function test_resend_sends_a_new_link_only_to_unverified_accounts(): void
    {
        Mail::fake();
        $this->customer();
        $this->customer(['email' => 'maria@example.com', 'email_verified_at' => now()]);

        $this->from('/login')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('customer.verification.resend'), ['email' => 'juan@example.com'])
            ->assertRedirect('/login');

        $this->from('/login')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->post(route('customer.verification.resend'), ['email' => 'maria@example.com'])
            ->assertRedirect('/login');

        Mail::assertSent(VerifyCustomerEmail::class, 1);
        Mail::assertSent(VerifyCustomerEmail::class, fn ($mail) => $mail->hasTo('juan@example.com'));
    }
}
