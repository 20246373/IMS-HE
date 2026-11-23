<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
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
            'email' => "staff{$n}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_employee_signs_up_with_email_and_password_and_waits_for_a_role(): void
    {
        $this->post('/register/employee', ['first_name' => 'Nina', 'last_name' => 'Reyes', 'email' => 'New.Hire@Example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('staff.pending'));

        $user = User::where('email', 'new.hire@example.com')->firstOrFail();
        $this->assertSame(User::PENDING, $user->role);
        $this->assertSame('Nina Reyes', $user->displayName());
        $this->assertNotSame('secret123', $user->password);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Registered', 'table_name' => 'users', 'record_id' => $user->user_id]);
    }

    public function test_employee_signup_rejects_bad_input_and_emails_already_in_use(): void
    {
        $this->post('/register/employee', ['email' => 'nope', 'password' => 'short'])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);

        $this->staff(User::EMPLOYEE, ['email' => 'taken@example.com']);
        $this->post('/register/employee', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@example.com', 'password' => 'secret123'])
            ->assertSessionHasErrors(['email' => 'This email is already registered.']);

        Customer::create(['email' => 'shopper@example.com', 'password' => 'secret123']);
        $this->post('/register/employee', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'shopper@example.com', 'password' => 'secret123'])->assertSessionHasErrors('email');
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

    public function test_staff_log_in_with_email_and_pending_goes_to_the_waiting_page(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK, ['email' => 'clerk1@example.com']);

        $this->post('/login', ['login' => 'CLERK1@example.com', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($clerk, 'web');

        $this->post('/logout');
        $this->assertGuest('web');

        // Usernames are gone: only the email logs in.
        $this->post('/login', ['login' => 'clerk1', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest('web');

        $this->post('/logout');
        $this->staff(User::PENDING, ['email' => 'wait@example.com']);
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

    public function test_approving_links_the_one_employee_record_with_the_same_name(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $employee = fn (string $first, string $last) => Employee::create(['first_name' => $first, 'last_name' => $last, 'position' => 'Clerk', 'daily_rate' => 500, 'date_hired' => today(), 'status' => 'Active']);
        $nina = $employee('Nina', 'Reyes');
        $employee('Jose', 'Cruz');
        $employee('Jose', 'Cruz');

        $matched = $this->staff(User::PENDING, ['first_name' => 'nina', 'last_name' => 'REYES']);
        $this->actingAs($administrator)->post(route('users.role', $matched), ['role' => User::EMPLOYEE])->assertSessionHasNoErrors();
        $this->assertSame($nina->employee_id, $matched->fresh()->employee_id);

        // Two employees share the name, so neither is linked.
        $ambiguous = $this->staff(User::PENDING, ['first_name' => 'Jose', 'last_name' => 'Cruz']);
        $this->actingAs($administrator)->post(route('users.role', $ambiguous), ['role' => User::EMPLOYEE])->assertSessionHasNoErrors();
        $this->assertNull($ambiguous->fresh()->employee_id);
        $this->assertSame('Jose Cruz', $ambiguous->fresh()->displayName());
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
        $this->staff(User::PENDING, ['email' => 'wait@example.com']);
        $administrator = $this->staff(User::ADMINISTRATOR);

        $response = $this->actingAs($administrator)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Unassigned accounts')
            ->assertDontSee('Unassigned accounts (')
            ->assertSee('wait@example.com')
            ->assertSee('Approve')
            ->assertDontSee('<option value="Administrator">', false)
            ->assertDontSee('<option value="Pending">', false);
        $this->actingAs($administrator)->get(route('dashboard'))->assertOk()->assertSee('Unassigned accounts');
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

    public function test_dashboard_account_cards_link_to_the_accounts_page(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $this->staff(User::PENDING);

        $this->actingAs($administrator)->get(route('dashboard'))->assertOk()
            ->assertSee('href="'.route('users.index').'">Manage accounts</a>', false)
            ->assertSee('href="'.route('users.index').'#unassigned">Review assignments</a>', false);
    }

    public function test_audit_log_shows_date_time_and_the_persons_name(): void
    {
        $employee = Employee::create(['first_name' => 'Maria', 'last_name' => 'Santos', 'position' => 'Treasurer', 'daily_rate' => 800, 'date_hired' => today(), 'status' => 'Active']);
        $treasurer = $this->staff(User::TREASURER, ['employee_id' => $employee->employee_id]);
        AuditLog::record('Updated', 'products', 7, $treasurer->user_id);

        $this->actingAs($this->staff(User::ADMINISTRATOR))->get(route('audit.index'))->assertOk()
            ->assertSeeInOrder(['<th>Date</th>', '<th>Time</th>', '<th>Name</th>', '<th>Action</th>'], false)
            ->assertSee('Maria Santos')
            ->assertDontSee('<th>Table</th>', false)
            ->assertDontSee('<th>Record</th>', false);
    }

    public function test_audit_log_says_what_each_action_applied_to_and_filters_employees_or_customers(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        AuditLog::record('Completed', 'invoices', 12, $administrator->user_id);
        AuditLog::record('Updated', 'customers', 3, $administrator->user_id);
        AuditLog::record('Locked', 'users', 5, $administrator->user_id);
        AuditLog::record('Login', 'users', $administrator->user_id, $administrator->user_id);
        $this->actingAs($administrator);

        // The action says what it applied to, both in the list and in the Action filter.
        $this->get(route('audit.index'))->assertOk()
            ->assertSee('Completed online order #12')
            ->assertSee('Updated customer account #3')
            ->assertSee('Locked employee account #5')
            ->assertSee('Logged in')
            ->assertSee('<option value="Completed|invoices"', false)
            ->assertSee('Completed online order</option>', false);

        $this->get(route('audit.index', ['action' => 'Completed|invoices']))->assertOk()
            ->assertSee('Completed online order #12')->assertDontSee('Updated customer account #3');

        // About: entries about employees or about customers (online orders count as customer entries).
        $this->get(route('audit.index', ['about' => 'customers']))->assertOk()
            ->assertSee('Completed online order #12')->assertSee('Updated customer account #3')
            ->assertDontSee('Locked employee account #5');
        $this->get(route('audit.index', ['about' => 'employees']))->assertOk()
            ->assertSee('Locked employee account #5')->assertDontSee('Completed online order #12');
        $this->get(route('audit.index', ['about' => 'suppliers']))->assertSessionHasErrors('about');
    }

    public function test_seeded_administrator_can_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/login', ['login' => 'admin@example.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->firstOrFail(), 'web');

        // Welcome message and the sidebar box show the person's name, not the email.
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Welcome back, Anton Sanchez')
            ->assertSee('<strong>Anton Sanchez</strong>', false);
    }

    public function test_user_edit_updates_name_email_role_status_links_the_matching_employee_and_audits(): void
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
        $target = $this->staff(User::EMPLOYEE, ['email' => 'original@example.com']);

        $this->actingAs($administrator)->put(route('users.update', $target), [
            'first_name' => 'Linked',
            'last_name' => 'Employee',
            'email' => 'Changed@Example.com',
            'role' => User::TREASURER,
            'status' => User::STATUS_LOCKED,
        ])->assertRedirect(route('users.index'));

        $updated = $target->fresh();
        $this->assertSame('changed@example.com', $updated->email);
        $this->assertSame(User::TREASURER, $updated->role);
        $this->assertSame(User::STATUS_LOCKED, $updated->status);
        $this->assertSame($employee->employee_id, $updated->employee_id);
        $this->assertSame('Linked Employee', $updated->displayName());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Updated',
            'table_name' => 'users',
            'record_id' => $target->user_id,
        ]);

        $updated->update(['login_attempt_count' => 2]);
        $this->actingAs($administrator)->put(route('users.update', $updated), [
            'first_name' => 'Linked',
            'last_name' => 'Employee',
            'email' => 'changed@example.com',
            'role' => User::TREASURER,
            'status' => User::STATUS_ACTIVE,
        ])->assertRedirect(route('users.index'));

        $this->assertSame(0, $updated->fresh()->login_attempt_count);
    }

    public function test_pages_show_a_back_link_and_the_title_outside_the_cards_except_the_dashboard(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);

        $this->actingAs($administrator)->get(route('users.index'))->assertOk()
            ->assertSee('class="back-link no-print"', false)
            ->assertSeeInOrder(['<div class="page-head">', '<h1>Employee Accounts</h1>', '<div class="card'], false);

        $this->actingAs($administrator)->get(route('dashboard'))->assertOk()
            ->assertDontSee('class="back-link', false);
    }

    public function test_user_edit_requires_every_field_and_a_unique_email(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $target = $this->staff(User::EMPLOYEE, ['email' => 'target@example.com']);
        Customer::create(['email' => 'shopper@example.com', 'password' => 'secret123']);

        $this->actingAs($administrator)->put(route('users.update', $target), ['email' => '', 'role' => User::EMPLOYEE, 'status' => User::STATUS_ACTIVE])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email']);

        $this->actingAs($administrator)->put(route('users.update', $target), ['first_name' => 'A', 'last_name' => 'B', 'email' => $administrator->email, 'role' => User::EMPLOYEE, 'status' => User::STATUS_ACTIVE])
            ->assertSessionHasErrors('email');
        $this->actingAs($administrator)->put(route('users.update', $target), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'shopper@example.com', 'role' => User::EMPLOYEE, 'status' => User::STATUS_ACTIVE])
            ->assertSessionHasErrors('email');

        $this->assertSame('target@example.com', $target->fresh()->email);
    }

    public function test_administrator_cannot_change_own_role_or_lock_own_account(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $fields = ['first_name' => 'Anton', 'last_name' => 'Sanchez', 'email' => $administrator->email];

        $this->actingAs($administrator)->put(route('users.update', $administrator), $fields + [
            'role' => User::PRESIDENT,
            'status' => User::STATUS_ACTIVE,
        ])->assertSessionHasErrors('role');

        $this->actingAs($administrator)->put(route('users.update', $administrator), $fields + [
            'role' => User::ADMINISTRATOR,
            'status' => User::STATUS_LOCKED,
        ])->assertSessionHasErrors('status');
    }

    public function test_administrator_creates_an_account_and_it_links_the_employee_with_the_same_name(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $employee = Employee::create([
            'first_name' => 'New',
            'last_name' => 'Cashier',
            'position' => 'Cashier',
            'daily_rate' => 500,
            'date_hired' => today(),
            'status' => 'Active',
        ]);

        // No password fields: the default is the last name + 123.
        $this->actingAs($administrator)->get(route('users.create'))->assertOk()->assertSee('First name')
            ->assertDontSee('name="password"', false);

        $this->actingAs($administrator)->post(route('users.store'), [
            'first_name' => 'new',
            'last_name' => 'cashier',
            'email' => 'Cashier@Example.com',
            'role' => User::EMPLOYEE,
        ])->assertRedirect(route('users.index'))->assertSessionHas('status', 'Account created.');
        $this->assertTrue(Hash::check('cashier123', User::where('email', 'cashier@example.com')->value('password')));   // default: last name + 123

        $account = User::where('email', 'cashier@example.com')->firstOrFail();
        $this->assertSame($employee->employee_id, $account->employee_id);
        $this->assertSame(User::EMPLOYEE, $account->role);
        $this->assertSame(User::STATUS_ACTIVE, $account->status);
        $this->assertTrue($account->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created', 'table_name' => 'users', 'record_id' => $account->user_id]);

        // The email cannot be used twice.
        $this->actingAs($administrator)->post(route('users.store'), [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'cashier@example.com',
            'role' => User::EMPLOYEE,
        ])->assertSessionHasErrors('email');
    }

    public function test_an_account_the_administrator_creates_must_change_its_default_password_at_first_login(): void
    {
        $administrator = $this->staff(User::ADMINISTRATOR);
        $this->actingAs($administrator)->post(route('users.store'), [
            'first_name' => 'Nina', 'last_name' => 'Reyes', 'email' => 'nina@example.com', 'role' => User::EMPLOYEE,
        ]);
        $default = 'reyes123';   // last name in lowercase + 123
        $this->assertTrue(Hash::check($default, User::where('email', 'nina@example.com')->value('password')));
        $this->post('/logout');

        // First login goes to the change-password page, and every other page sends them back to it.
        // A wrong password on the new account explains that it still uses the default password (without saying what it is).
        $this->post('/login', ['login' => 'nina@example.com', 'password' => 'wrong-guess'])
            ->assertSessionHasErrors(['login' => 'This account is new and still uses its default password. Log in with the default password your administrator gave you, then you must change your password.']);
        $this->post('/login', ['login' => $administrator->email, 'password' => 'wrong-guess'])
            ->assertSessionHasErrors(['login' => 'Invalid email or password.']);   // ordinary accounts: the usual message

        $this->post('/login', ['login' => 'nina@example.com', 'password' => $default])->assertRedirect(route('password.change'));
        $this->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('sales.create'))->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk()->assertSee('Change your password');

        $this->post(route('password.change.update'), ['password' => 'mypassword1', 'password_confirmation' => 'different1'])
            ->assertSessionHasErrors(['password' => 'The two passwords do not match.']);
        $this->post(route('password.change.update'), ['password' => $default, 'password_confirmation' => $default])
            ->assertSessionHasErrors(['password' => 'Choose a new password, not the default one.']);

        $this->post(route('password.change.update'), ['password' => 'mypassword1', 'password_confirmation' => 'mypassword1'])
            ->assertRedirect(route('dashboard'));
        $nina = User::where('email', 'nina@example.com')->firstOrFail();
        $this->assertFalse($nina->must_change_password);
        $this->assertTrue(Hash::check('mypassword1', $nina->password));
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('password.change'))->assertRedirect(route('dashboard'));   // nothing to change any more
    }

    public function test_login_errors_show_once_in_the_box_above_not_under_the_fields(): void
    {
        $this->staff(User::EMPLOYEE, ['email' => 'once@example.com']);

        $this->from(route('login', ['as' => 'employee']))
            ->post('/login', ['login' => 'once@example.com', 'password' => 'wrong-guess']);
        $page = $this->get(route('login', ['as' => 'employee']))->assertOk();

        $this->assertSame(1, substr_count($page->getContent(), 'Invalid email or password.'));
        $page->assertDontSee('class="field-error"', false);
    }

    public function test_sign_up_pages_ask_to_confirm_the_password(): void
    {
        $this->get(route('staff.register'))->assertOk()->assertSee('name="password_confirmation"', false);
        $this->get(route('customer.register'))->assertOk()->assertSee('name="password_confirmation"', false);

        $this->post('/register/employee', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'ab@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret999'])
            ->assertSessionHasErrors(['password' => 'The two passwords do not match.']);
        $this->post(route('customer.register'), ['email' => 'cd@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret999'])
            ->assertSessionHasErrors(['password' => 'The two passwords do not match.']);
        $this->assertDatabaseMissing('users', ['email' => 'ab@example.com']);
        $this->assertDatabaseMissing('customers', ['email' => 'cd@example.com']);

        // Employees who sign up themselves are never asked to change their password.
        $this->post('/register/employee', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'ab@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('staff.pending'));
        $this->assertFalse(User::where('email', 'ab@example.com')->firstOrFail()->must_change_password);
    }

    public function test_only_the_administrator_can_create_accounts(): void
    {
        $this->actingAs($this->staff(User::PRESIDENT))->get(route('users.create'))->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT))->post(route('users.store'), [])->assertForbidden();
    }
}
