<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $this->normalize($request);
        $data = $request->validate($this->rules() + $this->emailRules());

        $employee = DB::transaction(function () use ($data): Employee {
            $employee = Employee::create(Arr::except($data, ['email']));
            AuditLog::record('Created', 'employees', $employee->employee_id);
            $this->saveLogin($employee, $data['email']);

            return $employee;
        });

        return redirect()->route('employees.index')->with('status', "{$employee->full_name} added. Their login is {$data['email']}: the administrator gives them a role, and they set a password with \"Forgot your password?\".");
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
        $this->normalize($request);
        $data = $request->validate($this->editableRules() + $this->emailRules($employee->user));

        DB::transaction(function () use ($employee, $data): void {
            $employee->update(Arr::except($data, ['email']));
            AuditLog::record('Updated', 'employees', $employee->employee_id);
            $this->saveLogin($employee, $data['email']);
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

    private function normalize(Request $request): void
    {
        $request->merge([
            'daily_rate' => Money::parse($request->input('daily_rate')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
    }

    /**
     * Every employee needs an email because it is their login. It must be unique across staff and customers.
     *
     * @return array<string, array<int, mixed>>
     */
    private function emailRules(?User $account = null): array
    {
        return [
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($account?->user_id, 'user_id'), Rule::unique('customers', 'email')],
        ];
    }

    /**
     * Keeps the employee's login email in step with the form. An employee without an account gets one that waits for
     * the Administrator to give it a role (like a self sign-up); they set their own password with "Forgot your password?".
     */
    private function saveLogin(Employee $employee, string $email): void
    {
        $account = $employee->user;

        if ($account) {
            if ($account->email !== $email) {
                $account->update(['email' => $email]);
                AuditLog::record('Updated', 'users', $account->user_id);
            }

            return;
        }

        $account = User::create([
            'email' => $email,
            'password' => Str::random(40),
            'role' => User::PENDING,
            'status' => User::STATUS_ACTIVE,
            'login_attempt_count' => 0,
            'employee_id' => $employee->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
        ]);
        AuditLog::record('Created', 'users', $account->user_id);
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
            'sss_no' => ['required', 'string', 'max:20'],
            'philhealth_no' => ['required', 'string', 'max:20'],
            'pagibig_no' => ['required', 'string', 'max:20'],
            'tin_no' => ['required', 'string', 'max:20'],
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
            'sss_no' => ['required', 'string', 'max:20'],
            'philhealth_no' => ['required', 'string', 'max:20'],
            'pagibig_no' => ['required', 'string', 'max:20'],
            'tin_no' => ['required', 'string', 'max:20'],
            'status' => ['required', Rule::in(['Active', 'Resigned'])],
        ];
    }
}
