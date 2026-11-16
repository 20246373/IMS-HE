<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, ?int $employeeId = null): User
    {
        return User::create([
            'username' => 'u' . uniqid(),
            'password' => 'password',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'employee_id' => $employeeId,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create($overrides + [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'position' => 'Cashier',
            'daily_rate' => 1234.50,
            'date_hired' => '2026-01-15',
            'status' => 'Active',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'position' => 'Warehouse Helper',
            'daily_rate' => '650.00',
            'sss_no' => '1234567890',
            'philhealth_no' => '123456789012',
            'pagibig_no' => '1234 5678 9012',
            'tin_no' => '123456789',
            'date_hired' => '2026-03-01',
            'status' => 'Active',
        ];
    }

    // ---- access ----------------------------------------------------------------------------

    public function test_president_and_treasurer_can_open_employee_pages(): void
    {
        $employee = $this->employee();

        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/employees')->assertOk();
            $this->actingAs($user)->get('/employees/create')->assertOk();
            $this->actingAs($user)->get("/employees/{$employee->employee_id}/edit")->assertOk();
        }
    }

    public function test_other_roles_are_forbidden_and_guests_are_sent_to_login(): void
    {
        $employee = $this->employee();

        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR, User::INVENTORY_CLERK, 'Administrator', 'Pending'] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/employees')->assertForbidden();
            $this->actingAs($user)->post('/employees', $this->payload())->assertForbidden();
            $this->actingAs($user)->put("/employees/{$employee->employee_id}", ['position' => 'X', 'daily_rate' => 1])->assertForbidden();
        }

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/employees')->assertRedirect(route('staff.login'));
        $this->post('/employees', $this->payload())->assertRedirect(route('staff.login'));
    }

    // ---- create ----------------------------------------------------------------------------

    public function test_can_add_an_employee_with_all_government_numbers(): void
    {
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->post('/employees', $this->payload())
            ->assertRedirect('/employees')
            ->assertSessionHas('status');

        $employee = Employee::where('last_name', 'Dela Cruz')->firstOrFail();
        $this->assertSame('Juan', $employee->first_name);
        $this->assertSame('Warehouse Helper', $employee->position);
        $this->assertEquals(650.00, (float) $employee->daily_rate);
        // Numbers typed without dashes (or with spaces) are saved in the standard format.
        $this->assertSame('12-3456789-0', $employee->sss_no);
        $this->assertSame('12-345678901-2', $employee->philhealth_no);
        $this->assertSame('1234-5678-9012', $employee->pagibig_no);
        $this->assertSame('123-456-789', $employee->tin_no);
        $this->assertSame('2026-03-01', $employee->date_hired->toDateString());
        $this->assertSame('Active', $employee->status);

        $this->assertTrue(AuditLog::where(['action' => 'Created', 'table_name' => 'employees', 'record_id' => $employee->employee_id])->exists());
    }

    public function test_treasurer_can_add_an_employee_and_optional_fields_may_be_blank(): void
    {
        $treasurer = $this->staff(User::TREASURER);

        $this->actingAs($treasurer)->post('/employees', [
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'position' => 'Clerk', 'daily_rate' => '700', 'status' => 'Active',
        ])->assertRedirect('/employees');

        $employee = Employee::where('last_name', 'Lopez')->firstOrFail();
        $this->assertNull($employee->sss_no);
        $this->assertNull($employee->philhealth_no);
        $this->assertNull($employee->pagibig_no);
        $this->assertNull($employee->tin_no);
        $this->assertNull($employee->date_hired);
    }

    public function test_required_fields_are_validated(): void
    {
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->post('/employees', [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'position', 'daily_rate', 'status']);

        $this->actingAs($president)->post('/employees', $this->payload(['daily_rate' => '-5']))
            ->assertSessionHasErrors('daily_rate');
        $this->actingAs($president)->post('/employees', $this->payload(['status' => 'Retired']))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_government_numbers_must_have_the_right_number_of_digits(): void
    {
        $president = $this->staff(User::PRESIDENT);

        foreach (['sss_no' => '12345', 'philhealth_no' => '12345678901', 'pagibig_no' => 'abcd-1234-5678', 'tin_no' => '1234567'] as $field => $bad) {
            $this->actingAs($president)->post('/employees', $this->payload([$field => $bad]))->assertSessionHasErrors($field);
        }

        // A 12-digit TIN (with branch code) is also valid.
        $this->actingAs($president)->post('/employees', $this->payload(['tin_no' => '123-456-789-000']))->assertSessionHasNoErrors();
        $this->assertSame('123-456-789-000', Employee::first()->tin_no);
    }

    public function test_a_government_number_cannot_belong_to_two_employees(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $this->employee(['sss_no' => '12-3456789-0']);

        $this->actingAs($president)->post('/employees', $this->payload(['sss_no' => '12 3456789 0']))
            ->assertSessionHasErrors('sss_no');

        $this->assertDatabaseCount('employees', 1);
    }

    // ---- edit ------------------------------------------------------------------------------

    public function test_edit_changes_only_position_rate_and_government_numbers(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee();

        $this->actingAs($treasurer)->put("/employees/{$employee->employee_id}", [
            'position' => 'Senior Cashier',
            'daily_rate' => '800.00',
            'sss_no' => '9876543210',
            'philhealth_no' => '',
            'pagibig_no' => '111122223333',
            'tin_no' => '987654321',
            // Read-only on edit (comment 7): must be ignored even if someone posts them.
            'first_name' => 'Hacked',
            'last_name' => 'Hacked',
            'date_hired' => '2000-01-01',
            'status' => 'Resigned',
        ])->assertRedirect('/employees');

        $employee->refresh();
        $this->assertSame('Senior Cashier', $employee->position);
        $this->assertEquals(800.00, (float) $employee->daily_rate);
        $this->assertSame('98-7654321-0', $employee->sss_no);
        $this->assertNull($employee->philhealth_no);
        $this->assertSame('1111-2222-3333', $employee->pagibig_no);
        $this->assertSame('987-654-321', $employee->tin_no);

        $this->assertSame('Maria', $employee->first_name);
        $this->assertSame('Santos', $employee->last_name);
        $this->assertSame('2026-01-15', $employee->date_hired->toDateString());
        $this->assertSame('Active', $employee->status);

        $this->assertTrue(AuditLog::where(['action' => 'Updated', 'table_name' => 'employees', 'record_id' => $employee->employee_id])->exists());
    }

    public function test_keeping_your_own_government_number_on_edit_is_not_a_duplicate(): void
    {
        $employee = $this->employee(['sss_no' => '12-3456789-0']);

        $this->actingAs($this->staff(User::PRESIDENT))->put("/employees/{$employee->employee_id}", [
            'position' => 'Cashier', 'daily_rate' => '1234.50', 'sss_no' => '12-3456789-0',
        ])->assertSessionHasNoErrors();
    }

    // ---- screens ---------------------------------------------------------------------------

    public function test_forms_mark_required_and_optional_fields(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $employee = $this->employee();

        $create = $this->actingAs($president)->get('/employees/create')->assertOk();
        $create->assertSee('<span class="req">*</span> Required field', false);
        $create->assertSee('(optional)', false);
        // SSS, PhilHealth, Pag-IBIG, TIN and date hired are optional, plus one mention in the form note.
        $this->assertSame(6, substr_count($create->getContent(), '(optional)'));

        $edit = $this->actingAs($president)->get("/employees/{$employee->employee_id}/edit")->assertOk();
        $edit->assertSee('<span class="req">*</span> Required field', false);
        // First name, last name, date hired and status are shown but cannot be edited.
        $this->assertSame(4, substr_count($edit->getContent(), '(read-only)'));
        $this->assertSame(4, substr_count($edit->getContent(), ' readonly>'));
        $edit->assertDontSee('name="first_name"', false);
        $edit->assertDontSee('name="date_hired"', false);
    }

    public function test_daily_rate_is_shown_in_peso_format(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $employee = $this->employee(['daily_rate' => 1234.50]);

        $this->actingAs($president)->get('/employees')->assertSee('₱1,234.50');
        $this->actingAs($president)->get("/employees/{$employee->employee_id}/edit")->assertSee('₱1,234.50');
    }

    public function test_success_message_uses_the_shared_alert_component(): void
    {
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->followingRedirects()->post('/employees', $this->payload())
            ->assertSee('role="alert"', false)
            ->assertSee('class="ok"', false)
            ->assertSee('Employee Juan Dela Cruz added.');

        $this->actingAs($president)->followingRedirects()->post('/employees', $this->payload(['daily_rate' => '0']))
            ->assertSee('class="err"', false);
    }

    // ---- account link ----------------------------------------------------------------------

    public function test_employee_can_be_linked_to_a_user_account_and_the_link_is_shown(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $employee = $this->employee();

        $this->actingAs($president)->get('/employees')->assertSee('Not linked');

        // Linking is done on the Accounts screen (existing UserController, users.employee_id).
        $account = $this->staff(User::EMPLOYEE);
        $this->actingAs($president)->put("/users/{$account->user_id}", [
            'username' => $account->username,
            'role' => User::EMPLOYEE,
            'employee_id' => $employee->employee_id,
        ])->assertRedirect('/users');

        $this->assertSame($employee->employee_id, $account->refresh()->employee_id);
        $this->assertSame($account->user_id, $employee->refresh()->user->user_id);
        $this->actingAs($president)->get('/employees')->assertSee($account->username)->assertDontSee('Not linked');
    }
}
