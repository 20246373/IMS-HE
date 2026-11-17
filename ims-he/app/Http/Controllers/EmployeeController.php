<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('employees.index', [
            'employees' => Employee::with('user')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'employee' => new Employee(['status' => 'Active']),
            'statuses' => ['Active', 'Resigned'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['daily_rate' => Money::parse($request->input('daily_rate'))]);
        $data = $request->validate($this->rules());

        $employee = DB::transaction(function () use ($data): Employee {
            $employee = Employee::create($data);
            AuditLog::record('Created', 'employees', $employee->employee_id);

            return $employee;
        });

        return redirect()->route('employees.index')->with('status', "{$employee->full_name} added.");
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'statuses' => ['Active', 'Resigned'],
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $request->merge(['daily_rate' => Money::parse($request->input('daily_rate'))]);
        $data = $request->validate($this->editableRules());

        DB::transaction(function () use ($employee, $data): void {
            $employee->update($data);
            AuditLog::record('Updated', 'employees', $employee->employee_id);
        });

        return redirect()->route('employees.index')->with('status', 'Employee updated.');
    }

    public function resign(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee): void {
            $employee->update(['status' => 'Resigned']);
            AuditLog::record('Resigned', 'employees', $employee->employee_id);
        });

        return redirect()->route('employees.index')->with('status', "{$employee->full_name} resigned.");
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:50'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'sss_no' => ['nullable', 'string', 'max:20'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'pagibig_no' => ['nullable', 'string', 'max:20'],
            'tin_no' => ['nullable', 'string', 'max:20'],
            'date_hired' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['Active', 'Resigned'])],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function editableRules(): array
    {
        return [
            'position' => ['required', 'string', 'max:50'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'sss_no' => ['nullable', 'string', 'max:20'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'pagibig_no' => ['nullable', 'string', 'max:20'],
            'tin_no' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['Active', 'Resigned'])],
        ];
    }
}
