<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            // Employees who signed up themselves and are waiting for a role come first.
            'pending' => User::where('role', User::PENDING)->orderBy('created_at')->get(),
            'users' => User::with('employee')->where('role', '!=', User::PENDING)->orderBy('email')->get(),
            'roles' => User::roleChoices(),
        ]);
    }

    /** SDD 4.1 User Account Management: the Administrator creates a staff account directly. */
    public function create()
    {
        return view('users.create', ['roles' => User::roleChoices()]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email'), Rule::unique('customers', 'email')],
            'role' => ['required', Rule::in(User::roleChoices())],
        ], ['email.unique' => 'This email is already used by another account.']);

        // Default password (last name + 123) that the employee must replace at their first login.
        $defaultPassword = User::defaultPassword($data['last_name']);

        $user = DB::transaction(function () use ($data, $defaultPassword) {
            $user = new User([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $defaultPassword,
                'must_change_password' => true,
                'status' => User::STATUS_ACTIVE,
                'login_attempt_count' => 0,
            ]);
            $user->linkMatchingEmployee();
            $user->save();
            AuditLog::record('Created', 'users', $user->user_id);

            return $user;
        });

        return redirect()->route('users.index')->with('status', 'Account created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => User::roleChoices(),
            'statuses' => [User::STATUS_ACTIVE, User::STATUS_LOCKED],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        // Every field on the account form is required; the email must stay unique across staff and customers.
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'), Rule::unique('customers', 'email')],
            'role' => ['required', Rule::in($user->hasRole(User::ADMINISTRATOR)
                ? [...User::roleChoices(), $user->role]
                : User::roleChoices())],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_LOCKED])],
        ], ['email.unique' => 'This email is already used by another account.']);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'You cannot change your own role.'])->withInput();
        }

        if ($user->is($request->user()) && $data['status'] === User::STATUS_LOCKED) {
            return back()->withErrors(['status' => 'You cannot lock your own account.'])->withInput();
        }

        $user->fill([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        if ($data['status'] === User::STATUS_ACTIVE && $user->getOriginal('status') === User::STATUS_LOCKED) {
            $user->login_attempt_count = 0;
        }

        $user->linkMatchingEmployee();
        $user->save();

        AuditLog::record('Updated', 'users', $user->user_id);

        return redirect()->route('users.index')->with('status', 'Account updated.');
    }

    /** Gives a role to an employee who signed up and is waiting (role "Pending"). */
    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(User::roleChoices())]]);

        if ($user->is($request->user())) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        // The name comes from sign-up; the account is linked to the matching employee record when there is one.
        $user->role = $data['role'];
        $user->linkMatchingEmployee();
        $user->save();
        AuditLog::record('Role Assigned', 'users', $user->user_id);

        return back()->with('status', "{$user->displayName()} is now {$data['role']}.");
    }

    public function lock(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot lock your own account.']);
        }

        $user->update(['status' => User::STATUS_LOCKED]);
        AuditLog::record('Locked', 'users', $user->user_id);

        return back()->with('status', "{$user->displayName()} locked.");
    }

    public function unlock(User $user)
    {
        $user->update(['status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]);
        AuditLog::record('Unlocked', 'users', $user->user_id);

        return back()->with('status', "{$user->displayName()} unlocked.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->update(['password' => $data['password'], 'login_attempt_count' => 0]);
        AuditLog::record('Password Reset', 'users', $user->user_id);

        return back()->with('status', "Password reset for {$user->displayName()}.");
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
        $name = $user->displayName();
        $user->delete();
        AuditLog::record('Deleted', 'users', $id);

        return redirect()->route('users.index')->with('status', "Account {$name} deleted.");
    }
}
