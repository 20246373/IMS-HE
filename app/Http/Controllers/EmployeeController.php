<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('employees.index', [
            'employees' => Employee::orderBy('last_name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'position' => 'required|string|max:50',
            'daily_rate' => 'required|numeric|min:0',
            'sss_no' => 'nullable|string|max:20',
            'philhealth_no' => 'nullable|string|max:20',
            'pagibig_no' => 'nullable|string|max:20',
            'tin_no' => 'nullable|string|max:20',
            'date_hired' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
        ]);

        Employee::create($data);

        return redirect()->route('employees.index')->with('status', 'Employee added.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'position' => 'required|string|max:50',
            'daily_rate' => 'required|numeric|min:0',
            'sss_no' => 'nullable|string|max:20',
            'philhealth_no' => 'nullable|string|max:20',
            'pagibig_no' => 'nullable|string|max:20',
            'tin_no' => 'nullable|string|max:20',
            'date_hired' => 'nullable|date',
            'status' => 'required|in:Active,Inactive',
        ]);

        $employee->update($data);

        return redirect()->route('employees.index')->with('status', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->user()->exists()) {
            return back()->withErrors(['employee' => 'Cannot delete employee with a user account. Deactivate the account first.']);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('status', 'Employee deleted.');
    }
}
