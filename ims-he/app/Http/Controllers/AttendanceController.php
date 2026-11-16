<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Attendance screen (Task B2).
 *  Employee:                    clocks in and out for themselves and sees only their own records.
 *  Store Supervisor, Treasurer: see every record and can add or correct records.
 */
class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isManager = $user->hasRole(User::STORE_SUPERVISOR, User::TREASURER);
        [$from, $to] = $this->dateRange($request);

        $employee = $isManager ? null : $user->employee;

        $records = Attendance::with('employee')
            ->whereBetween('work_date', [$from, $to])
            ->when(! $isManager, fn ($q) => $q->where('employee_id', $employee?->employee_id ?? 0))
            ->orderByDesc('work_date')->orderBy('employee_id')
            ->paginate(31)->withQueryString();

        return view('attendance.index', [
            'records' => $records,
            'from' => $from,
            'to' => $to,
            'isManager' => $isManager,
            'linked' => $isManager || $employee !== null,
            'today' => $employee
                ? Attendance::where('employee_id', $employee->employee_id)->where('work_date', today()->toDateString())->first()
                : null,
        ]);
    }

    public function clockIn(Request $request)
    {
        $this->attendance->clockIn($this->ownEmployee($request));

        return back()->with('status', 'Clocked in at ' . now()->format('g:i A') . '.');
    }

    public function clockOut(Request $request)
    {
        $record = $this->attendance->clockOut($this->ownEmployee($request));

        $message = 'Clocked out at ' . now()->format('g:i A') . '.';
        if ($record->overtime_hours > 0) {
            $message .= ' Overtime: ' . number_format($record->overtime_hours, 2) . ' hours.';
        }

        return back()->with('status', $message);
    }

    public function create()
    {
        return view('attendance.create', [
            'employees' => Employee::where('status', Employee::STATUS_ACTIVE)->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,employee_id',
            'work_date' => 'required|date|before_or_equal:today',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
        ]);

        $record = $this->attendance->record(
            (int) $data['employee_id'], $data['work_date'], $data['time_in'], $data['time_out'] ?? null, $request->user()->user_id,
        );

        return redirect()->route('attendance.index', ['from' => $record->work_date->toDateString(), 'to' => $record->work_date->toDateString()])
            ->with('status', 'Attendance record added.');
    }

    public function edit(Attendance $attendance)
    {
        return view('attendance.edit', ['attendance' => $attendance->load('employee')]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
        ]);

        $this->attendance->correct($attendance, $data['time_in'], $data['time_out'] ?? null, $request->user()->user_id);

        return redirect()->route('attendance.index', ['from' => $attendance->work_date->toDateString(), 'to' => $attendance->work_date->toDateString()])
            ->with('status', 'Attendance record corrected.');
    }

    /** The employee record behind the logged-in Employee account. */
    private function ownEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw ValidationException::withMessages(['attendance' => 'Your account is not linked to an employee record. Ask the President to link it.']);
        }

        return $employee;
    }

    /** Date filter: both dates are required when the filter is used; defaults to the current month. */
    private function dateRange(Request $request): array
    {
        if (! $request->hasAny(['from', 'to'])) {
            return [today()->startOfMonth()->toDateString(), today()->toDateString()];
        }

        $data = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        return [Carbon::parse($data['from'])->toDateString(), Carbon::parse($data['to'])->toDateString()];
    }
}
