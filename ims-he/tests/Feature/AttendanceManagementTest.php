<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, ?int $employeeId = null): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'employee_id' => $employeeId,
            'email' => "attendance-{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'first_name' => 'Mia',
            'last_name' => 'Santos',
            'position' => 'Clerk',
            'daily_rate' => 500,
            'date_hired' => '2025-01-15',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_employee_can_clock_in_and_out_once_for_today(): void
    {
        $employee = $this->employee();
        $user = $this->staff(User::EMPLOYEE, $employee->employee_id);
        $this->travelTo('2026-11-17 08:00:00');

        $this->actingAs($user)->post(route('attendance.clock-in'))->assertRedirect();
        $this->travelTo('2026-11-17 17:30:00');
        $this->actingAs($user)->post(route('attendance.clock-out'))->assertRedirect();

        $record = Attendance::firstOrFail();
        $this->assertSame($employee->employee_id, $record->employee_id);
        $this->assertSame(1.5, (float) $record->overtime_hours);
        $this->assertDatabaseCount('attendance', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clocked In', 'table_name' => 'attendance']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clocked Out', 'table_name' => 'attendance']);
    }

    public function test_second_clock_in_on_same_day_is_refused_without_duplicate(): void
    {
        $employee = $this->employee();
        $user = $this->staff(User::EMPLOYEE, $employee->employee_id);
        $this->travelTo('2026-11-17 08:00:00');

        $this->actingAs($user)->post(route('attendance.clock-in'))->assertRedirect();
        $this->actingAs($user)->post(route('attendance.clock-in'))
            ->assertSessionHasErrors(['attendance' => 'You have already clocked in today.']);

        $this->assertDatabaseCount('attendance', 1);
    }

    public function test_unlinked_employee_sees_warning_and_cannot_clock_in(): void
    {
        $user = $this->staff(User::EMPLOYEE);

        $this->actingAs($user)->get(route('attendance.my'))
            ->assertOk()
            ->assertSee('no employee record linked yet');
        $this->actingAs($user)->post(route('attendance.clock-in'))
            ->assertSessionHasErrors('attendance');
    }

    public function test_overtime_is_rounded_to_quarter_hours(): void
    {
        $service = app(AttendanceService::class);

        $this->assertSame(0.0, $service->calculateOvertime('08:00', '16:00'));
        $this->assertSame(0.25, $service->calculateOvertime('08:00', '16:15'));
        $this->assertSame(1.5, $service->calculateOvertime('08:00', '17:30'));
    }

    public function test_staff_can_correct_overtime_and_update_or_create_without_duplicates(): void
    {
        $employee = $this->employee();
        $treasurer = $this->staff(User::TREASURER);
        $date = '2026-11-17';
        $payload = [
            'work_date' => $date,
            'records' => [
                $employee->employee_id => [
                    'employee_id' => $employee->employee_id,
                    'time_in' => '08:00',
                    'time_out' => '17:00',
                    'overtime_hours' => '0.25',
                ],
            ],
        ];

        $this->actingAs($treasurer)->post(route('attendance.day.save'), $payload)->assertRedirect();
        $this->actingAs($treasurer)->post(route('attendance.day.save'), $payload)->assertRedirect();

        $record = Attendance::firstOrFail();
        $this->assertSame(0.25, (float) $record->overtime_hours);
        $this->assertDatabaseCount('attendance', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Updated', 'table_name' => 'attendance']);
    }

    public function test_staff_cannot_save_clock_out_before_clock_in(): void
    {
        $employee = $this->employee();
        $supervisor = $this->staff(User::STORE_SUPERVISOR);

        $this->actingAs($supervisor)->post(route('attendance.day.save'), [
            'work_date' => '2026-11-17',
            'records' => [
                $employee->employee_id => [
                    'employee_id' => $employee->employee_id,
                    'time_in' => '17:00',
                    'time_out' => '08:00',
                    'overtime_hours' => null,
                ],
            ],
        ])->assertSessionHasErrors('records');

        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_attendance_roles_are_restricted(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->staff(User::EMPLOYEE, $employee->employee_id))
            ->get(route('attendance.my'))->assertOk();
        $this->actingAs($this->staff(User::STORE_SUPERVISOR))
            ->get(route('attendance.day'))->assertOk();
        $this->actingAs($this->staff(User::TREASURER))
            ->get(route('attendance.period'))->assertOk();
        $this->actingAs($this->staff(User::ADMINISTRATOR))
            ->get(route('attendance.day'))->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT))
            ->get(route('attendance.day'))->assertForbidden();
    }
}
