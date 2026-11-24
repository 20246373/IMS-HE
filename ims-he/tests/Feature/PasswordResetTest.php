<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordEmail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'email' => 'customer@example.com',
            'password' => 'old-password',
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'email' => 'staff@example.com',
            'password' => 'old-password',
            'role' => User::EMPLOYEE,
            'status' => User::STATUS_ACTIVE,
        ], $overrides));
    }

    private function resetUrl(string $guard, $account, ?string $passwordHash = null, $expiration = null): string
    {
        $id = $guard === 'customer' ? $account->customer_id : $account->user_id;

        return URL::temporarySignedRoute('password.reset', $expiration ?? now()->addHour(), [
            'guard' => $guard,
            'id' => $id,
            'hash' => hash('sha256', $passwordHash ?? $account->password),
        ]);
    }

    public function test_customer_can_reset_once_and_mail_is_sent_to_the_customer(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->post(route('password.email'), ['email' => 'CUSTOMER@example.com'])
            ->assertSessionHas('status', 'If that email exists, we sent a link.');
        Mail::assertSent(ResetPasswordEmail::class, fn ($mail) => $mail->hasTo('customer@example.com'));

        $url = $this->resetUrl('customer', $customer);
        $this->get($url)->assertOk()->assertSee('Reset password');
        $this->post($url, ['password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password', $customer->fresh()->password));
        $this->get($url)->assertRedirect(route('password.forgot'))->assertSessionHasErrors('email');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Password Reset', 'table_name' => 'customers', 'customer_id' => $customer->customer_id, 'user_id' => null]);
    }

    public function test_staff_can_reset_even_when_locked_or_pending_and_is_audited(): void
    {
        foreach ([User::STATUS_LOCKED, User::STATUS_ACTIVE] as $status) {
            $user = $this->user([
                'email' => $status === User::STATUS_LOCKED ? 'locked@example.com' : 'pending@example.com',
                'role' => $status === User::STATUS_LOCKED ? User::EMPLOYEE : User::PENDING,
                'status' => $status,
            ]);

            $this->get($this->resetUrl('web', $user))->assertOk();
            $this->post($this->resetUrl('web', $user), [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect(route('login'));
            $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
            $this->assertSame($status, $user->fresh()->status);
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'Password Reset',
                'table_name' => 'users',
                'record_id' => $user->user_id,
            ]);
        }
    }

    public function test_unknown_email_gets_the_same_response_and_no_mail(): void
    {
        Mail::fake();

        $response = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

        $response->assertSessionHas('status', 'If that email exists, we sent a link.');
        Mail::assertNothingSent();
    }

    public function test_expired_tampered_and_unsigned_links_are_rejected(): void
    {
        $customer = $this->customer();
        $valid = $this->resetUrl('customer', $customer);
        $expired = $this->resetUrl('customer', $customer, null, now()->subMinute());
        $tampered = str_replace('customer', 'web', $valid);

        foreach ([$expired, $tampered, route('password.reset', [
            'guard' => 'customer',
            'id' => $customer->customer_id,
            'hash' => hash('sha256', $customer->password),
        ])] as $url) {
            $this->get($url)->assertRedirect(route('password.forgot'))->assertSessionHasErrors('email');
        }
    }

    public function test_audit_log_filters_by_user_action_and_date_range(): void
    {
        $admin = $this->user(['email' => 'admin@example.com', 'role' => User::ADMINISTRATOR]);
        $other = $this->user(['email' => 'other@example.com']);
        AuditLog::create(['user_id' => $admin->user_id, 'action' => 'Password Reset', 'table_name' => 'users', 'record_id' => $admin->user_id, 'logged_at' => '2026-01-10 10:00:00']);
        AuditLog::create(['user_id' => $other->user_id, 'action' => 'Login', 'table_name' => 'users', 'record_id' => $other->user_id, 'logged_at' => '2026-02-10 10:00:00']);

        $this->actingAs($admin)->get(route('audit.index', [
            'user_id' => $admin->user_id,
            'action' => 'Password Reset',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]))->assertOk()
            ->assertSee('Password Reset')
            ->assertDontSee('<td>Login</td>', false)
            ->assertSee('name="user_id"', false)
            ->assertSee('name="action"', false);
    }
}
