<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'username' => "emp-user-{$n}",
            'email' => "emp{$n}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'position' => 'Cashier',
            'daily_rate' => 800,
            'status' => 'Active',
        ], $overrides);
    }

    public function test_president_and_treasurer_can_manage_employee_records(): void
    {
        foreach ([User::PRESIDENT, User::TREASURER] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/employees')->assertOk();
            $this->actingAs($user)->get('/employees/create')->assertOk();

            $this->actingAs($user)->post('/employees', $this->payload(['last_name' => "Santos{$role}"]))
                ->assertSessionHasNoErrors();

            $employee = Employee::latest('employee_id')->firstOrFail();
            $this->assertEquals(800.0, (float) $employee->daily_rate);

            $this->actingAs($user)->put("/employees/{$employee->employee_id}", $this->payload([
                'last_name' => 'Santos-Updated', 'daily_rate' => 900, 'status' => 'Resigned',
            ]))->assertSessionHasNoErrors();

            $this->assertEquals('Resigned', $employee->fresh()->status);
            $this->assertEquals(900.0, (float) $employee->fresh()->daily_rate);
        }
    }

    public function test_other_roles_cannot_manage_employees(): void
    {
        $employee = Employee::create([
            'first_name' => 'A', 'last_name' => 'B', 'position' => 'Clerk',
            'daily_rate' => 500, 'status' => 'Active',
        ]);

        foreach ([User::STORE_SUPERVISOR, User::INVENTORY_CLERK, User::EMPLOYEE, User::ADMINISTRATOR] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user)->get('/employees')->assertForbidden();
            $this->actingAs($user)->post('/employees', $this->payload())->assertForbidden();
            $this->actingAs($user)->put("/employees/{$employee->employee_id}", $this->payload())->assertForbidden();
        }
    }

    public function test_employee_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->staff(User::TREASURER))->post('/employees', $this->payload([
            'daily_rate' => -50,
            'first_name' => '',
        ]))->assertSessionHasErrors(['daily_rate', 'first_name']);

        $this->assertEquals(0, Employee::count());
    }
}
