<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

// Employees sign up with email + password, wait as "Pending", and the Administrator gives them a role.
class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

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

    public function test_administrator_gives_a_role_and_the_employee_gets_access(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $pending = $this->staff(User::PENDING);

        $this->from(route('users.index'))
            ->actingAs($administrator)->post(route('users.role', $pending), ['role' => User::INVENTORY_CLERK])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSame(User::INVENTORY_CLERK, $pending->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Role Assigned', 'table_name' => 'users', 'record_id' => $pending->user_id]);

        $this->actingAs($pending->fresh())->get(route('products.index'))->assertOk();
        $this->actingAs($pending->fresh())->get(route('users.index'))->assertForbidden();   // only what the role allows
    }

    public function test_only_the_administrator_can_give_roles_and_only_real_roles(): void
    {
        $pending = $this->staff(User::PENDING);

        $this->actingAs($this->staff(User::STORE_SUPERVISOR))->post(route('users.role', $pending), ['role' => User::EMPLOYEE])->assertForbidden();
        $this->assertSame(User::PENDING, $pending->fresh()->role);

        $this->actingAs($this->staff(User::ADMINISTRATOR))->post(route('users.role', $pending), ['role' => 'Admin'])->assertSessionHasErrors('role');
        $this->actingAs($this->staff(User::ADMINISTRATOR))->post(route('users.role', $pending), ['role' => User::PENDING])->assertSessionHasErrors('role');
        $this->assertSame(User::PENDING, $pending->fresh()->role);
    }

    public function test_administrator_sees_waiting_employees_on_the_accounts_page_and_dashboard(): void
    {
        $this->staff(User::PENDING, ['username' => null, 'email' => 'wait@example.com']);
        $administrator = $this->staff(User::ADMINISTRATOR);

        $response = $this->actingAs($administrator)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Waiting for a role')
            ->assertSee('wait@example.com')
            ->assertSee('Approve')
            ->assertDontSee('<option value="Administrator">', false)
            ->assertDontSee('<option value="Pending">', false);
        $this->actingAs($administrator)->get(route('dashboard'))->assertOk()->assertSee('Waiting for a role');
    }

    public function test_president_cannot_access_account_management(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $pending = $this->staff(User::PENDING);

        $this->actingAs($president)->get(route('users.index'))->assertForbidden();
        $this->actingAs($president)->post(route('users.role', $pending), ['role' => User::EMPLOYEE])->assertForbidden();
        $this->actingAs($president)->get(route('audit.index'))->assertForbidden();
    }

    public function test_administrator_can_access_account_management_but_not_business_modules(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);

        $this->actingAs($administrator)->get(route('users.index'))->assertOk();
        $this->actingAs($administrator)->get(route('audit.index'))->assertOk();
        $this->actingAs($administrator)->get(route('products.index'))->assertForbidden();
        $this->actingAs($administrator)->get(route('suppliers.index'))->assertForbidden();
    }

    public function test_seeded_administrator_can_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/login', ['login' => 'admin@example.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->firstOrFail(), 'web');
    }

    public function test_user_edit_ignores_identity_fields_updates_allowed_fields_and_audits_changes(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $employee = Employee::create([
            'first_name' => 'Linked',
            'last_name' => 'Employee',
            'position' => 'Clerk',
            'daily_rate' => 500,
            'date_hired' => today(),
            'status' => 'Active',
        ]);
        $target = $this->staff(User::EMPLOYEE, ['email' => 'original@example.com', 'username' => 'original']);

        $this->actingAs($administrator)->put(route('users.update', $target), [
            'email' => 'changed@example.com',
            'username' => 'changed',
            'role' => User::TREASURER,
            'status' => User::STATUS_LOCKED,
            'employee_id' => $employee->employee_id,
        ])->assertRedirect(route('users.index'));

        $updated = $target->fresh();
        $this->assertSame('original@example.com', $updated->email);
        $this->assertSame('original', $updated->username);
        $this->assertSame(User::TREASURER, $updated->role);
        $this->assertSame(User::STATUS_LOCKED, $updated->status);
        $this->assertSame($employee->employee_id, $updated->employee_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Updated',
            'table_name' => 'users',
            'record_id' => $target->user_id,
        ]);

        $updated->update(['login_attempt_count' => 2]);
        $this->actingAs($administrator)->put(route('users.update', $updated), [
            'role' => User::TREASURER,
            'status' => User::STATUS_ACTIVE,
            'employee_id' => $employee->employee_id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame(0, $updated->fresh()->login_attempt_count);
    }

    public function test_administrator_cannot_change_own_role_or_lock_own_account(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);

        $this->actingAs($administrator)->put(route('users.update', $administrator), [
            'role' => User::PRESIDENT,
            'status' => User::STATUS_ACTIVE,
            'employee_id' => null,
        ])->assertSessionHasErrors('role');

        $this->actingAs($administrator)->put(route('users.update', $administrator), [
            'role' => User::ADMINISTRATOR,
            'status' => User::STATUS_LOCKED,
            'employee_id' => null,
        ])->assertSessionHasErrors('status');
    }
}
