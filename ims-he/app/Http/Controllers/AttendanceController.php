<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

// Attendance tab of the Payroll module (SDD 7.2.9, SRS UC17).
class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,employee_id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $records = Attendance::with('employee')
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($filters['date'] ?? null, fn ($q, $d) => $q->whereDate('work_date', $d))
            ->orderByDesc('work_date')
            ->orderByDesc('attendance_id')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.index', [
            'records' => $records,
            'employees' => Employee::where('status', 'Active')->orderBy('last_name')->orderBy('first_name')->get(),
            'filters' => $filters,
        ]);
    }

    /** Clock in (defaults to today). */
    public function clockIn(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
            'work_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        $this->attendance->clockIn($employee, $data['work_date'] ?? null);

        return back()->with('status', "{$employee->full_name} clocked in.");
    }

    /** Log a complete day (time in and out) directly - used for past days or manual entry. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i', 'after:time_in'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        try {
            $this->attendance->logDay($employee, $data['work_date'], $data['time_in'], $data['time_out']);
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['employee_id' => 'That employee already has an attendance record for this date.'])->withInput();
        }

        return back()->with('status', 'Attendance logged.');
    }

    public function clockOut(Attendance $attendance)
    {
        $this->attendance->clockOut($attendance);

        return back()->with('status', 'Clocked out.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i', 'after:time_in'],
        ]);

        $this->attendance->updateTimes($attendance, $data['time_in'], $data['time_out']);

        return back()->with('status', 'Attendance updated.');
    }
}
