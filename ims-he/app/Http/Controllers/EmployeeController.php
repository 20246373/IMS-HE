<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Support\GovernmentId;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Employee management (Task B1). President and Treasurer only (routes/web.php).
 * Account linking lives on the Accounts screen (users.employee_id); this screen shows who is linked.
 * On edit only position, daily rate and the government numbers can change (comment 7).
 */
class EmployeeController extends Controller
{
    /** Government number fields: form field => GovernmentId type. */
    private const ID_FIELDS = [
        'sss_no' => GovernmentId::SSS,
        'philhealth_no' => GovernmentId::PHILHEALTH,
        'pagibig_no' => GovernmentId::PAGIBIG,
        'tin_no' => GovernmentId::TIN,
    ];

    public function index()
    {
        return view('employees.index', [
            'employees' => Employee::with('user')->orderBy('last_name')->orderBy('first_name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL .\'-]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL .\'-]+$/u'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in([Employee::STATUS_ACTIVE, Employee::STATUS_RESIGNED])],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, apostrophes, periods and hyphens.',
            'last_name.regex' => 'Last name may only contain letters, spaces, apostrophes, periods and hyphens.',
        ]);

        $employee = Employee::create($this->withFormattedIds($data, null));

        AuditLog::record('Created', 'employees', $employee->employee_id);

        return redirect()->route('employees.index')->with('status', "Employee {$employee->full_name} added.");
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', ['employee' => $employee->load('user')]);
    }

    public function update(Request $request, Employee $employee)
    {
        // Only validated keys are saved, so first name, last name, date hired and status in the request are ignored.
        $data = $request->validate($this->rules());

        $employee->update($this->withFormattedIds($data, $employee));

        AuditLog::record('Updated', 'employees', $employee->employee_id);

        return redirect()->route('employees.index')->with('status', "Employee {$employee->full_name} updated.");
    }

    /** Rules for the fields that exist on both create and edit. */
    private function rules(): array
    {
        $rules = [
            'position' => ['required', 'string', 'max:50'],
            'daily_rate' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];

        foreach (self::ID_FIELDS as $field => $type) {
            $rules[$field] = ['nullable', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail) use ($type) {
                if (GovernmentId::normalize($type, $value) === null) {
                    $fail(GovernmentId::LABELS[$type] . ' must look like ' . GovernmentId::EXAMPLES[$type] . '.');
                }
            }];
        }

        return $rules;
    }

    /** Stores government numbers in their standard format and refuses a number that another employee already has. */
    private function withFormattedIds(array $data, ?Employee $employee): array
    {
        foreach (self::ID_FIELDS as $field => $type) {
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                $data[$field] = null;
                continue;
            }

            $data[$field] = GovernmentId::normalize($type, $data[$field]);

            $taken = Employee::where($field, $data[$field])
                ->when($employee, fn ($q) => $q->where('employee_id', '!=', $employee->employee_id))
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([$field => GovernmentId::LABELS[$type] . ' already belongs to another employee.']);
            }
        }

        return $data;
    }
}
