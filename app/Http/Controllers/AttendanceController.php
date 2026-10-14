<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with('employee')
            ->when($request->date, fn ($q, $date) => $q->where('work_date', $date))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->orderByDesc('work_date')
            ->paginate(30);

        $employees = Employee::where('status', 'Active')->orderBy('last_name')->get();

        return view('attendance.index', [
            'attendances' => $attendances,
            'employees' => $employees,
        ]);
    }

    public function create()
    {
        return view('attendance.create', [
            'employees' => Employee::where('status', 'Active')->orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,employee_id',
            'work_date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'work_date' => $data['work_date']],
            [
                'time_in' => $data['time_in'],
                'time_out' => $data['time_out'] ?? null,
                'overtime_hours' => $data['overtime_hours'] ?? 0,
            ]
        );

        return redirect()->route('attendance.index')->with('status', 'Attendance recorded.');
    }

    public function edit(Attendance $attendance)
    {
        return view('attendance.edit', [
            'attendance' => $attendance,
            'employees' => Employee::where('status', 'Active')->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id', Rule::unique('attendance')->where('work_date', $attendance->work_date)->ignore($attendance->attendance_id, 'attendance_id')],
            'work_date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        $attendance->update($data);

        return redirect()->route('attendance.index')->with('status', 'Attendance updated.');
    }
}
