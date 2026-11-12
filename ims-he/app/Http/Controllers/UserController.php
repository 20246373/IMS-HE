<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const MESSAGES = ['email.unique' => 'This email is already registered.'];

    public function index()
    {
        return view('users.index', [
            // Employees who signed up themselves and are waiting for a role come first.
            'pending'   => User::where('role', User::PENDING)->orderBy('created_at')->get(),
            'users'     => User::with('employee')->where('role', '!=', User::PENDING)->orderBy('username')->orderBy('email')->get(),
            'roles'     => User::ROLES,
            'employees' => $this->employeeOptions(null),
        ]);
    }

    public function create()
    {
        return view('users.create', ['employees' => $this->employeeOptions(null), 'roles' => User::ROLES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'       => ['nullable', 'email', 'max:100', 'required_without:username', 'unique:users,email', 'unique:customers,email'],
            'username'    => 'nullable|alpha_num|max:50|required_without:email|unique:users,username',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => ['required', Rule::in(User::ROLES)],
            'employee_id' => 'nullable|exists:employees,employee_id|unique:users,employee_id',
        ], self::MESSAGES);

        $user = User::create([
            'email'       => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'username'    => $data['username'] ?? null,
            'password'    => $data['password'],            // hashed by the model cast
            'role'        => $data['role'],
            'employee_id' => $data['employee_id'] ?? null,
            'status'      => User::STATUS_ACTIVE,
        ]);

        AuditLog::record('Created', 'users', $user->user_id);

        return redirect()->route('users.index')->with('status', "Account {$user->label()} created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'employees' => $this->employeeOptions($user), 'roles' => User::roleChoices()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'email'       => ['nullable', 'email', 'max:100', 'required_without:username', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'), 'unique:customers,email'],
            'username'    => ['nullable', 'alpha_num', 'max:50', 'required_without:email', Rule::unique('users', 'username')->ignore($user->user_id, 'user_id')],
            'role'        => ['required', Rule::in(User::roleChoices())],
            'employee_id' => ['nullable', 'exists:employees,employee_id', Rule::unique('users', 'employee_id')->ignore($user->user_id, 'user_id')],
        ], self::MESSAGES);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'You cannot change your own role.'])->withInput();
        }

        $user->update([
            'email'       => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'username'    => $data['username'] ?? null,
            'role'        => $data['role'],
            'employee_id' => $data['employee_id'] ?? null,
        ]);

        AuditLog::record('Updated', 'users', $user->user_id);

        return redirect()->route('users.index')->with('status', 'Account updated.');
    }

    /** Gives a role to an employee who signed up and is waiting (role "Pending"). */
    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role'        => ['required', Rule::in(User::ROLES)],
            'employee_id' => ['nullable', 'exists:employees,employee_id', Rule::unique('users', 'employee_id')->ignore($user->user_id, 'user_id')],
        ]);

        if ($user->is($request->user())) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->update(['role' => $data['role'], 'employee_id' => $data['employee_id'] ?? null]);
        AuditLog::record('Role Assigned', 'users', $user->user_id);

        return back()->with('status', "{$user->label()} is now {$data['role']}.");
    }

    public function lock(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot lock your own account.']);
        }

        $user->update(['status' => User::STATUS_LOCKED]);
        AuditLog::record('Locked', 'users', $user->user_id);

        return back()->with('status', "{$user->label()} locked.");
    }

    public function unlock(User $user)
    {
        $user->update(['status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]);
        AuditLog::record('Unlocked', 'users', $user->user_id);

        return back()->with('status', "{$user->label()} unlocked.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->update(['password' => $data['password'], 'login_attempt_count' => 0]);
        AuditLog::record('Password Reset', 'users', $user->user_id);

        return back()->with('status', "Password reset for {$user->label()}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $hasRecords = DB::table('payrolls')->where('processed_by', $user->user_id)->exists()
            || DB::table('ledger_entries')->where('recorded_by', $user->user_id)->exists();

        if ($hasRecords) {
            return back()->withErrors(['user' => 'This account has payroll/ledger records and cannot be deleted. Lock it instead.']);
        }

        $id = $user->user_id;
        $name = $user->label();
        $user->delete();
        AuditLog::record('Deleted', 'users', $id);

        return redirect()->route('users.index')->with('status', "Account {$name} deleted.");
    }

    /** Active employees that do not already have an account (plus the one linked to $user). */
    private function employeeOptions(?User $user)
    {
        $taken = User::whereNotNull('employee_id')
            ->when($user, fn ($q) => $q->where('user_id', '!=', $user->user_id))
            ->pluck('employee_id');

        return Employee::where('status', 'Active')
            ->whereNotIn('employee_id', $taken)
            ->orderBy('last_name')
            ->get();
    }
}
