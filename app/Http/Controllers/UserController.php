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
    public function index()
    {
        return view('users.index', ['users' => User::with('employee')->orderBy('username')->get()]);
    }

    public function create()
    {
        return view('users.create', ['employees' => $this->employeeOptions(null)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'    => 'required|alpha_num|max:50|unique:users,username',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => ['required', Rule::in(User::ROLES)],
            'employee_id' => 'nullable|exists:employees,employee_id|unique:users,employee_id',
        ]);

        $user = User::create([
            'username'    => $data['username'],
            'password'    => $data['password'],            // hashed by the model cast
            'role'        => $data['role'],
            'employee_id' => $data['employee_id'] ?? null,
            'status'      => User::STATUS_ACTIVE,
        ]);

        AuditLog::record('Created', 'users', $user->user_id);

        return redirect()->route('users.index')->with('status', "Account {$user->username} created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'employees' => $this->employeeOptions($user)]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'    => ['required', 'alpha_num', 'max:50', Rule::unique('users', 'username')->ignore($user->user_id, 'user_id')],
            'role'        => ['required', Rule::in(User::ROLES)],
            'employee_id' => ['nullable', 'exists:employees,employee_id', Rule::unique('users', 'employee_id')->ignore($user->user_id, 'user_id')],
        ]);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'You cannot change your own role.'])->withInput();
        }

        $user->update([
            'username'    => $data['username'],
            'role'        => $data['role'],
            'employee_id' => $data['employee_id'] ?? null,
        ]);

        AuditLog::record('Updated', 'users', $user->user_id);

        return redirect()->route('users.index')->with('status', 'Account updated.');
    }

    public function lock(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot lock your own account.']);
        }

        $user->update(['status' => User::STATUS_LOCKED]);
        AuditLog::record('Locked', 'users', $user->user_id);

        return back()->with('status', "{$user->username} locked.");
    }

    public function unlock(User $user)
    {
        $user->update(['status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]);
        AuditLog::record('Unlocked', 'users', $user->user_id);

        return back()->with('status', "{$user->username} unlocked.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->update(['password' => $data['password'], 'login_attempt_count' => 0]);
        AuditLog::record('Password Reset', 'users', $user->user_id);

        return back()->with('status', "Password reset for {$user->username}.");
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
        $name = $user->username;
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
