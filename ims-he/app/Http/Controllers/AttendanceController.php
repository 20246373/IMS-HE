<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function my(Request $request): View
    {
        $employee = $request->user()->employee;
        $record = $employee
            ? Attendance::where('employee_id', $employee->employee_id)->whereDate('work_date', today())->first()
            : null;

        return view('attendance.my', compact('employee', 'record'));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $employee = $this->linkedEmployee($request->user());
        $date = today()->toDateString();

        if (Attendance::where('employee_id', $employee->employee_id)->whereDate('work_date', $date)->exists()) {
            return back()->withErrors(['attendance' => 'You have already clocked in today.']);
        }

        DB::transaction(function () use ($employee, $date): void {
            $record = Attendance::create([
                'employee_id' => $employee->employee_id,
                'work_date' => $date,
                'time_in' => now()->format('H:i:s'),
                'overtime_hours' => 0,
            ]);
            AuditLog::record('Clocked In', 'attendance', $record->attendance_id);
        });

        return back()->with('status', 'Clocked in.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $employee = $this->linkedEmployee($request->user());
        $record = Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('work_date', today())
            ->first();

        if (! $record) {
            return back()->withErrors(['attendance' => 'Clock in before clocking out.']);
        }

        if ($record->time_out !== null) {
            return back()->withErrors(['attendance' => 'You have already clocked out today.']);
        }

        $timeOut = now();
        if ($timeOut->lessThanOrEqualTo(Carbon::parse($record->time_in))) {
            return back()->withErrors(['attendance' => 'Clock out must be after clock in.']);
        }

        DB::transaction(function () use ($record, $timeOut): void {
            $record->update([
                'time_out' => $timeOut->format('H:i:s'),
                'overtime_hours' => $this->attendance->calculateOvertime($record->time_in, $timeOut->format('H:i:s')),
            ]);
            AuditLog::record('Clocked Out', 'attendance', $record->attendance_id);
        });

        return back()->with('status', 'Clocked out.');
    }

    public function day(Request $request): View
    {
        $date = $this->dateFilter($request);
        $employees = Employee::where('status', 'Active')->orderBy('last_name')->orderBy('first_name')->get();
        $records = Attendance::whereDate('work_date', $date)->get()->keyBy('employee_id');

        return view('attendance.day', compact('date', 'employees', 'records'));
    }

    public function saveDay(Request $request): RedirectResponse
    {
        $data = $this->validatedDay($request);

        DB::transaction(function () use ($data): void {
            foreach ($data['records'] as $row) {
                if ($row['time_in'] === null && $row['time_out'] === null) {
                    continue;
                }

                $attributes = [
                    'time_in' => $row['time_in'],
                    'time_out' => $row['time_out'],
                    'overtime_hours' => $row['overtime_hours'] === null
                        ? $this->attendance->calculateOvertime($row['time_in'], $row['time_out'])
                        : (float) $row['overtime_hours'],
                ];
                $record = Attendance::where('employee_id', $row['employee_id'])
                    ->whereDate('work_date', $data['work_date'])
                    ->first();

                if ($record) {
                    $record->update($attributes);
                } else {
                    $record = Attendance::updateOrCreate(
                        ['employee_id' => $row['employee_id'], 'work_date' => $data['work_date']],
                        $attributes,
                    );
                }
                AuditLog::record('Updated', 'attendance', $record->attendance_id);
            }
        });

        return redirect()->route('attendance.day', ['date' => $data['work_date']])
            ->with('status', 'Attendance saved.');
    }

    public function period(Request $request): View
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $request->input('end_date', today()->toDateString()),
        ]);
        $filters = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'employee_id' => ['nullable', 'exists:employees,employee_id'],
        ]);

        return view('attendance.period', [
            'records' => Attendance::with('employee')
                ->whereBetween('work_date', [$filters['start_date'], $filters['end_date']])
                ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
                ->orderByDesc('work_date')
                ->orderBy('employee_id')
                ->paginate(20)
                ->withQueryString(),
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->get(),
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'employeeId' => $filters['employee_id'] ?? null,
        ]);
    }

    private function linkedEmployee(User $user): Employee
    {
        if (! $user->employee_id || ! $user->employee) {
            throw ValidationException::withMessages([
                'attendance' => 'Your account has no employee record linked yet.',
            ]);
        }

        return $user->employee;
    }

    private function dateFilter(Request $request): string
    {
        $request->merge(['date' => $request->input('date', today()->toDateString())]);

        return $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];
    }

    /**
     * @return array{work_date:string,records:array<int,array{employee_id:int,time_in:?string,time_out:?string,overtime_hours:?string}>}
     */
    private function validatedDay(Request $request): array
    {
        $data = $request->validate([
            'work_date' => ['required', 'date_format:Y-m-d'],
            'records' => ['required', 'array'],
            'records.*.employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
            'records.*.time_in' => ['nullable', 'date_format:H:i'],
            'records.*.time_out' => ['nullable', 'date_format:H:i'],
            'records.*.overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
        ]);

        foreach ($data['records'] as $row) {
            if ($row['time_out'] !== null && $row['time_in'] === null) {
                throw ValidationException::withMessages([
                    'records' => 'A clock-out requires a clock-in.',
                ]);
            }

            if ($row['time_in'] !== null && $row['time_out'] !== null
                && Carbon::createFromFormat('H:i', $row['time_out'])->lessThanOrEqualTo(Carbon::createFromFormat('H:i', $row['time_in']))) {
                throw ValidationException::withMessages([
                    'records' => 'Clock out must be after clock in.',
                ]);
            }
        }

        return $data;
    }
}
