<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('username', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->orderBy('role')->orderBy('username')
            ->paginate(15)->withQueryString();

        return view('accounts.index', ['users' => $users]);
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'username' => 'required|alpha_num|max:50|unique:users,username',
            'name'     => 'required|max:100',
            'email'    => 'nullable|email|max:255|unique:users,email',
            'role'     => ['required', Rule::in(User::STAFF_ROLES)],
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create($d); // password hashed (bcrypt) by the model cast
        AuditLog::record('account.created', $user, "Created {$user->username} as {$user->role}");

        return redirect()->route('accounts.index')->with('status', "Account {$user->username} created.");
    }

    public function edit(User $user)
    {
        return view('accounts.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $d = $request->validate([
            'name'  => 'required|max:100',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'  => [Rule::requiredIf($user->isStaff() && $user->id !== $request->user()->id), Rule::in(User::STAFF_ROLES)],
        ]);

        // Customers keep their role; you also cannot change your own role (avoids locking yourself out of admin).
        if (! $user->isStaff() || $user->id === $request->user()->id) {
            unset($d['role']);
        }

        $oldRole = $user->role;
        $user->update($d);

        $note = isset($d['role']) && $d['role'] !== $oldRole ? "Role {$oldRole} -> {$d['role']}" : 'Profile updated';
        AuditLog::record('account.updated', $user, $note);

        return redirect()->route('accounts.index')->with('status', "Account {$user->username} updated.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['account' => 'You cannot delete your own account.']);
        }

        AuditLog::record('account.deleted', $user, "Deleted {$user->username} ({$user->role})");
        $user->delete();

        return redirect()->route('accounts.index')->with('status', 'Account deleted.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $d = $request->validate(['password' => 'required|min:8|confirmed']);

        $user->update(['password' => $d['password'], 'login_attempt_count' => 0]);
        AuditLog::record('account.password_reset', $user, "Password reset for {$user->username}");

        return back()->with('status', 'Password reset.');
    }

    public function toggleLock(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['account' => 'You cannot lock your own account.']);
        }

        $locking = ! $user->is_locked;
        $user->update(['is_locked' => $locking, 'login_attempt_count' => 0]);
        AuditLog::record($locking ? 'account.locked' : 'account.unlocked', $user, ($locking ? 'Locked ' : 'Unlocked ') . $user->username);

        return back()->with('status', $locking ? 'Account locked.' : 'Account unlocked.');
    }
}
