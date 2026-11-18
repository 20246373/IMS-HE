<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'email' => "employee-manager-{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'position' => 'Clerk',
            'daily_rate' => 500,
            'date_hired' => '2025-01-15',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_president_can_add_employee_and_audit_it(): void
    {
        $president = $this->staff(User::PRESIDENT);

        $response = $this->actingAs($president)->post(route('employees.store'), [
            'first_name' => 'Lina',
            'last_name' => 'Cruz',
            'email' => 'Lina.Cruz@Example.com',
            'position' => 'Cashier',
            'daily_rate' => '₱650.50',
            'sss_no' => '34-1234567-8',
            'philhealth_no' => '12-345678901-2',
            'pagibig_no' => '1210-1234-5678',
            'tin_no' => '123-456-789-000',
            'date_hired' => '2025-02-01',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('employees.index'));
        $employee = Employee::where('first_name', 'Lina')->firstOrFail();
        $this->assertSame('650.50', number_format((float) $employee->daily_rate, 2, '.', ''));
        $account = User::where('email', 'lina.cruz@example.com')->firstOrFail();
        $this->assertSame($employee->employee_id, $account->employee_id);
        $this->assertSame(User::PENDING, $account->role);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Created',
            'table_name' => 'employees',
            'record_id' => $employee->employee_id,
        ]);
    }

    public function test_index_searches_by_name_and_displays_linked_account_email_and_rate(): void
    {
        $employee = $this->employee();
        User::create([
            'employee_id' => $employee->employee_id,
            'email' => 'ana@example.com',
            'password' => 'secret123',
            'role' => User::EMPLOYEE,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->employee(['first_name' => 'Ben', 'last_name' => 'Reyes']);

        $this->actingAs($this->staff(User::TREASURER))
            ->get(route('employees.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertSee('Ana Santos')
            ->assertSee('ana@example.com')
            ->assertSee('₱500.00')
            ->assertDontSee('Ben Reyes');
    }

    public function test_treasurer_can_edit_employee_but_read_only_fields_are_ignored(): void
    {
        $treasurer = $this->staff(User::TREASURER);
        $employee = $this->employee();

        $this->actingAs($treasurer)->put(route('employees.update', $employee), [
            'first_name' => 'Tampered',
            'last_name' => 'Name',
            'date_hired' => '2030-01-01',
            'position' => 'Supervisor',
            'email' => 'ana.santos@example.com',
            'daily_rate' => '700.25',
            'sss_no' => '123',
            'philhealth_no' => '456',
            'pagibig_no' => '789',
            'tin_no' => '000',
            'status' => 'Active',
        ])->assertRedirect(route('employees.index'));

        $updated = $employee->fresh();
        $this->assertSame('Ana', $updated->first_name);
        $this->assertSame('Santos', $updated->last_name);
        $this->assertSame('2025-01-15', $updated->date_hired);
        $this->assertSame('Supervisor', $updated->position);
        $this->assertSame('123', $updated->sss_no);
        $this->assertSame('ana.santos@example.com', $updated->user->email);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Updated',
            'table_name' => 'employees',
            'record_id' => $employee->employee_id,
        ]);
    }

    public function test_edit_page_shows_the_fixed_name_and_hire_date(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->staff(User::PRESIDENT))->get(route('employees.edit', $employee))->assertOk()
            ->assertSee('value="Ana"', false)
            ->assertSee('value="Santos"', false)
            ->assertSee('value="Jan 15, 2025"', false);
    }

    public function test_resigning_employee_updates_status_and_audits_it(): void
    {
        $president = $this->staff(User::PRESIDENT);
        $employee = $this->employee();

        $this->actingAs($president)->post(route('employees.resign', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertSame('Resigned', $employee->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Resigned',
            'table_name' => 'employees',
            'record_id' => $employee->employee_id,
        ]);
    }

    public function test_employee_validation_rejects_missing_fields_and_future_hire_date(): void
    {
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->post(route('employees.store'), [
            'first_name' => '',
            'last_name' => '',
            'position' => '',
            'daily_rate' => 'not money',
            'date_hired' => now()->addDay()->toDateString(),
            'status' => 'Unknown',
        ])->assertSessionHasErrors([
            'first_name',
            'email',
            'sss_no',
            'philhealth_no',
            'pagibig_no',
            'tin_no',
            'last_name',
            'position',
            'daily_rate',
            'date_hired',
            'status',
        ]);

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_only_president_and_treasurer_can_manage_employees(): void
    {
        $employee = $this->employee();

        foreach ([User::EMPLOYEE, User::STORE_SUPERVISOR, User::ADMINISTRATOR] as $role) {
            $this->actingAs($this->staff($role))
                ->get(route('employees.index'))
                ->assertForbidden();
        }

        $this->assertSame('Active', $employee->fresh()->status);
    }
}
