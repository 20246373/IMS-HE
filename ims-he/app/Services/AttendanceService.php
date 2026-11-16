<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attendance rules (Task B2). One record per employee per date (also enforced by the unique key on
 * attendance.employee_id + work_date). Overtime = hours worked beyond config('payroll.hours_per_day'),
 * counted from time in to time out with no break deducted. A shift cannot cross midnight.
 * Dates inside a finalized payroll period are locked so a finalized payroll never disagrees with attendance.
 */
class AttendanceService
{
    /** Employee clocks in for the current date. */
    public function clockIn(Employee $employee, ?CarbonInterface $at = null): Attendance
    {
        $at ??= now();

        return DB::transaction(function () use ($employee, $at) {
            $this->assertActive($employee);
            $this->assertDateNotFinalized($at->toDateString());

            if ($this->existing($employee->employee_id, $at->toDateString())) {
                throw $this->error('You have already clocked in today.');
            }

            try {
                return Attendance::create([
                    'employee_id' => $employee->employee_id,
                    'work_date' => $at->toDateString(),
                    'time_in' => $at->format('H:i:s'),
                    'overtime_hours' => 0,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw $this->error('You have already clocked in today.');   // two taps at the same moment
            }
        });
    }

    /** Employee clocks out of today's open record. */
    public function clockOut(Employee $employee, ?CarbonInterface $at = null): Attendance
    {
        $at ??= now();

        return DB::transaction(function () use ($employee, $at) {
            $record = $this->existing($employee->employee_id, $at->toDateString(), lock: true);

            if (! $record) {
                throw $this->error('You have not clocked in today.');
            }
            if ($record->time_out !== null) {
                throw $this->error('You have already clocked out today.');
            }

            $this->assertDateNotFinalized($record->work_date->toDateString());
            $this->assertOrder($record->time_in, $at->format('H:i:s'));

            $record->update([
                'time_out' => $at->format('H:i:s'),
                'overtime_hours' => self::overtimeHours($record->time_in, $at->format('H:i:s')),
            ]);

            return $record;
        });
    }

    /** Supervisor or Treasurer adds a record that was never clocked (for example a forgotten clock-in). */
    public function record(int $employeeId, string $date, string $timeIn, ?string $timeOut, int $userId): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $timeIn, $timeOut, $userId) {
            $this->assertActive(Employee::findOrFail($employeeId));
            $this->assertDateNotFinalized($date);

            $timeIn = self::time($timeIn);
            $timeOut = $timeOut === null ? null : self::time($timeOut);
            $this->assertOrder($timeIn, $timeOut);

            if ($this->existing($employeeId, $date)) {
                throw $this->error('This employee already has an attendance record for that date. Correct the existing record instead.');
            }

            try {
                $record = Attendance::create([
                    'employee_id' => $employeeId,
                    'work_date' => $date,
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'overtime_hours' => self::overtimeHours($timeIn, $timeOut),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw $this->error('This employee already has an attendance record for that date.');
            }

            AuditLog::record('Added manually', 'attendance', $record->attendance_id, $userId);

            return $record;
        });
    }

    /** Supervisor or Treasurer corrects the times of an existing record; overtime is recomputed. */
    public function correct(Attendance $attendance, string $timeIn, ?string $timeOut, int $userId): Attendance
    {
        return DB::transaction(function () use ($attendance, $timeIn, $timeOut, $userId) {
            $this->assertDateNotFinalized($attendance->work_date->toDateString());

            $timeIn = self::time($timeIn);
            $timeOut = $timeOut === null ? null : self::time($timeOut);
            $this->assertOrder($timeIn, $timeOut);

            $attendance->update([
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'overtime_hours' => self::overtimeHours($timeIn, $timeOut),
            ]);

            AuditLog::record('Corrected', 'attendance', $attendance->attendance_id, $userId);

            return $attendance;
        });
    }

    /** Hours between time in and time out (null while the employee is still clocked in). */
    public static function hoursWorked(string $timeIn, ?string $timeOut): ?float
    {
        if ($timeOut === null) {
            return null;
        }

        return round((self::seconds($timeOut) - self::seconds($timeIn)) / 3600, 2);
    }

    /** Overtime = hours worked beyond the regular day, never negative. */
    public static function overtimeHours(string $timeIn, ?string $timeOut): float
    {
        $worked = self::hoursWorked($timeIn, $timeOut);

        return $worked === null ? 0.0 : round(max(0.0, $worked - (float) config('payroll.hours_per_day')), 2);
    }

    /** Accepts "H:i" (from the browser) or "H:i:s" and returns "H:i:s". */
    public static function time(string $value): string
    {
        return gmdate('H:i:s', self::seconds($value));
    }

    private static function seconds(string $time): int
    {
        return strtotime('1970-01-01 ' . $time . ' UTC');
    }

    private function existing(int $employeeId, string $date, bool $lock = false): ?Attendance
    {
        return Attendance::where('employee_id', $employeeId)->where('work_date', $date)
            ->when($lock, fn ($q) => $q->lockForUpdate())->first();
    }

    private function assertActive(Employee $employee): void
    {
        if ($employee->status !== Employee::STATUS_ACTIVE) {
            throw $this->error("{$employee->full_name} is {$employee->status} and cannot have new attendance.");
        }
    }

    private function assertOrder(string $timeIn, ?string $timeOut): void
    {
        if ($timeOut !== null && self::seconds($timeOut) <= self::seconds($timeIn)) {
            throw ValidationException::withMessages(['time_out' => 'Time out must be later than time in (shifts cannot cross midnight).']);
        }
    }

    private function assertDateNotFinalized(string $date): void
    {
        $payroll = Payroll::where('status', Payroll::STATUS_FINAL)
            ->where('period_start', '<=', $date)->where('period_end', '>=', $date)->first();

        if ($payroll) {
            throw $this->error("Attendance for {$date} is locked because payroll #{$payroll->payroll_id} is finalized.");
        }
    }

    private function error(string $message): ValidationException
    {
        return ValidationException::withMessages(['attendance' => $message]);
    }
}
