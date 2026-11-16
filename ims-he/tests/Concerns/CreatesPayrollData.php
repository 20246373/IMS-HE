<?php

namespace Tests\Concerns;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

/** Small builders shared by the attendance, payroll and payslip tests. */
trait CreatesPayrollData
{
    protected function staff(string $role, ?int $employeeId = null): User
    {
        return User::create([
            'username' => 'u' . uniqid(),
            'password' => 'password',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'employee_id' => $employeeId,
        ]);
    }

    protected function employee(array $overrides = []): Employee
    {
        return Employee::create($overrides + [
            'first_name' => 'Maria',
            'last_name' => 'Santos' . uniqid(),
            'position' => 'Cashier',
            'daily_rate' => 1000,
            'status' => 'Active',
        ]);
    }

    /** One attendance record per date from $start to $end (inclusive), $overtime hours on each record. */
    protected function attend(Employee $employee, string $start, string $end, float $overtime = 0): void
    {
        for ($d = strtotime($start); $d <= strtotime($end); $d += 86400) {
            Attendance::create([
                'employee_id' => $employee->employee_id,
                'work_date' => date('Y-m-d', $d),
                'time_in' => '08:00:00',
                'time_out' => '17:00:00',
                'overtime_hours' => $overtime,
            ]);
        }
    }
}
