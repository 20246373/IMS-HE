<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Employees tab of the Payroll module (SDD 7.2.8, SRS UC18): President + Treasurer.
class EmployeeController extends Controller
{
    public function index()
    {
        return view('employees.index', [
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('employees.form', ['employee' => new Employee(['status' => 'Active'])]);
    }

    public function store(Request $request)
    {
        Employee::create($this->validateEmployee($request));

        return redirect()->route('employees.index')->with('status', 'Employee added.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validateEmployee($request));

        return redirect()->route('employees.index')->with('status', 'Employee updated.');
    }

    /** @return array<string, mixed> */
    private function validateEmployee(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:50'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'sss_no' => ['nullable', 'string', 'max:20'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'pagibig_no' => ['nullable', 'string', 'max:20'],
            'tin_no' => ['nullable', 'string', 'max:20'],
            'date_hired' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['Active', 'Resigned'])],
        ]);
    }
}
