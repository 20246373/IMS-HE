<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Employees sign up with email + password, wait as "Pending", and the President gives them a role.
class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, array $overrides = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'username' => "staff{$n}",
            'email' => "staff{$n}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_employee_signs_up_with_email_and_password_and_waits_for_a_role(): void
    {
        $this->post('/register/employee', ['email' => 'New.Hire@Example.com', 'password' => 'secret123'])
            ->assertRedirect(route('staff.pending'));

        $user = User::where('email', 'new.hire@example.com')->firstOrFail();
        $this->assertSame(User::PENDING, $user->role);
        $this->assertNull($user->username);
        $this->assertNotSame('secret123', $user->password);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Registered', 'table_name' => 'users', 'record_id' => $user->user_id]);
    }

    public function test_employee_signup_rejects_bad_input_and_emails_already_in_use(): void
    {
        $this->post('/register/employee', ['email' => 'nope', 'password' => 'short'])->assertSessionHasErrors(['email', 'password']);

        $this->staff(User::EMPLOYEE, ['email' => 'taken@example.com']);
        $this->post('/register/employee', ['email' => 'taken@example.com', 'password' => 'secret123'])
            ->assertSessionHasErrors(['email' => 'This email is already registered.']);

        Customer::create(['email' => 'shopper@example.com', 'password' => 'secret123']);
        $this->post('/register/employee', ['email' => 'shopper@example.com', 'password' => 'secret123'])->assertSessionHasErrors('email');
    }

    public function test_pending_employee_only_sees_the_waiting_page(): void
    {
        $pending = $this->staff(User::PENDING);

        $this->actingAs($pending)->get(route('staff.pending'))->assertOk()->assertSee('Waiting for a role');
        $this->actingAs($pending)->get(route('dashboard'))->assertRedirect(route('staff.pending'));
        $this->actingAs($pending)->get(route('products.index'))->assertRedirect(route('staff.pending'));
    }

    public function test_employee_with_a_role_is_not_sent_to_the_waiting_page(): void
    {
        $this->actingAs($this->staff(User::INVENTORY_CLERK))->get(route('staff.pending'))->assertRedirect(route('dashboard'));
    }

    public function test_staff_log_in_with_email_or_username_and_pending_goes_to_the_waiting_page(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK, ['username' => 'clerk1', 'email' => 'clerk1@example.com']);

        $this->post('/login', ['login' => 'CLERK1@example.com', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($clerk, 'web');

        $this->post('/logout');
        $this->assertGuest('web');

        $this->post('/login', ['login' => 'clerk1', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($clerk, 'web');

        $this->post('/logout');
        $this->staff(User::PENDING, ['username' => null, 'email' => 'wait@example.com']);
        $this->post('/login', ['login' => 'wait@example.com', 'password' => 'secret123'])->assertRedirect(route('staff.pending'));
    }

    public function test_account_locks_after_three_wrong_passwords(): void
    {
        $user = $this->staff(User::EMPLOYEE, ['email' => 'lock@example.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['login' => 'lock@example.com', 'password' => 'wrong-password'])->assertSessionHasErrors('login');
        }

        $this->assertSame(User::STATUS_LOCKED, $user->fresh()->status);

        $this->post('/login', ['login' => 'lock@example.com', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest('web');
    }

    public function test_locking_an_account_ends_its_session(): void
    {
        $user = $this->staff(User::INVENTORY_CLERK);
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['status' => User::STATUS_LOCKED]);

        $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_president_gives_a_role_and_the_employee_gets_access(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $pending = $this->staff(User::PENDING);

        $this->actingAs($president)->post(route('users.role', $pending), ['role' => User::INVENTORY_CLERK])
            ->assertSessionHasNoErrors();

        $this->assertSame(User::INVENTORY_CLERK, $pending->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Role Assigned', 'table_name' => 'users', 'record_id' => $pending->user_id]);

        $this->actingAs($pending->fresh())->get(route('products.index'))->assertOk();
        $this->actingAs($pending->fresh())->get(route('users.index'))->assertForbidden();   // only what the role allows
    }

    public function test_only_the_president_can_give_roles_and_only_real_roles(): void
    {
        $pending = $this->staff(User::PENDING);

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->post(route('users.role', $pending), ['role' => User::EMPLOYEE])->assertForbidden();
        $this->assertSame(User::PENDING, $pending->fresh()->role);

        $this->actingAs($this->staff(User::PRESIDENT))->post(route('users.role', $pending), ['role' => 'Admin'])->assertSessionHasErrors('role');
        $this->actingAs($this->staff(User::PRESIDENT))->post(route('users.role', $pending), ['role' => User::PENDING])->assertSessionHasErrors('role');
        $this->assertSame(User::PENDING, $pending->fresh()->role);
    }

    public function test_president_sees_waiting_employees_on_the_accounts_page_and_dashboard(): void
    {
        $this->staff(User::PENDING, ['username' => null, 'email' => 'wait@example.com']);
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->get(route('users.index'))->assertOk()->assertSee('Waiting for a role')->assertSee('wait@example.com');
        $this->actingAs($president)->get(route('dashboard'))->assertOk()->assertSee('Waiting for a role');
    }
}
