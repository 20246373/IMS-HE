<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attendance records (SDD 7.2.9, SRS UC17): one row per employee per date,
 * enforced by the unique(employee_id, work_date) index. Clock-out computes
 * overtime_hours as worked time beyond config('payroll.hours_per_day').
 */
class AttendanceService
{
    /** Clock in for today. Returns the existing row if the employee already clocked in. */
    public function clockIn(Employee $employee, ?string $workDate = null, ?string $timeIn = null): Attendance
    {
        $workDate ??= today()->toDateString();
        $timeIn ??= now()->format('H:i:s');

        $existing = Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'employee_id' => "{$employee->full_name} already has an attendance record for {$workDate}.",
            ]);
        }

        return Attendance::create([
            'employee_id' => $employee->employee_id,
            'work_date' => $workDate,
            'time_in' => $timeIn,
            'time_out' => null,
            'overtime_hours' => 0,
        ]);
    }

    /**
     * Clock out: sets time_out and computes overtime_hours for the day.
     * Overnight shifts are not supported; time_out must be after time_in.
     */
    public function clockOut(Attendance $attendance, ?string $timeOut = null): Attendance
    {
        if ($attendance->time_out !== null) {
            throw ValidationException::withMessages([
                'attendance' => 'This attendance record is already clocked out.',
            ]);
        }

        $timeOut ??= now()->format('H:i:s');

        return DB::transaction(function () use ($attendance, $timeOut) {
            $attendance = Attendance::whereKey($attendance->attendance_id)->lockForUpdate()->firstOrFail();

            $workedMinutes = $this->minutesBetween((string) $attendance->time_in, $timeOut);

            if ($workedMinutes === null || $workedMinutes <= 0) {
                throw ValidationException::withMessages([
                    'time_out' => 'Clock-out time must be after clock-in time.',
                ]);
            }

            $standardMinutes = (int) config('payroll.hours_per_day', 8) * 60;
            $overtimeHours = round(max(0, $workedMinutes - $standardMinutes) / 60, 2);

            $attendance->update([
                'time_out' => $timeOut,
                'overtime_hours' => $overtimeHours,
            ]);

            return $attendance->fresh();
        });
    }

    /** Logs a complete attendance record (time in and out) for a past or current day. */
    public function logDay(Employee $employee, string $workDate, string $timeIn, string $timeOut): Attendance
    {
        $overtime = $this->overtimeFor($timeIn, $timeOut);

        if ($overtime === null) {
            throw ValidationException::withMessages([
                'time_out' => 'Clock-out time must be after clock-in time.',
            ]);
        }

        return Attendance::create([
            'employee_id' => $employee->employee_id,
            'work_date' => $workDate,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'overtime_hours' => $overtime,
        ]);
    }

    /** Corrects time_in/time_out on an existing record and recomputes overtime. */
    public function updateTimes(Attendance $attendance, string $timeIn, string $timeOut): Attendance
    {
        $overtime = $this->overtimeFor($timeIn, $timeOut);

        if ($overtime === null) {
            throw ValidationException::withMessages([
                'time_out' => 'Clock-out time must be after clock-in time.',
            ]);
        }

        $attendance->update([
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'overtime_hours' => $overtime,
        ]);

        return $attendance->fresh();
    }

    /** Overtime hours for a shift, or null when timeOut <= timeIn. */
    private function overtimeFor(string $timeIn, string $timeOut): ?float
    {
        $workedMinutes = $this->minutesBetween($timeIn, $timeOut);

        if ($workedMinutes === null) {
            return null;
        }

        $standardMinutes = (int) config('payroll.hours_per_day', 8) * 60;

        return round(max(0, $workedMinutes - $standardMinutes) / 60, 2);
    }

    /** Whole minutes between two HH:MM[:SS] times, or null if timeOut <= timeIn. */
    private function minutesBetween(string $timeIn, string $timeOut): ?int
    {
        $toMinutes = fn (string $t) => ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2);

        $diff = $toMinutes($timeOut) - $toMinutes($timeIn);

        return $diff > 0 ? $diff : null;
    }
}
