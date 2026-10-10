<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        $user = User::where('username', $data['username'])->first();

        if (! $user) {
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }
        if ($user->is_locked) {
            return back()->withErrors(['username' => 'Account locked after too many failed attempts. Contact the President.'])->onlyInput('username');
        }

        if (! Hash::check($data['password'], $user->password)) {
            $user->increment('login_attempt_count');
            if ($user->login_attempt_count >= User::MAX_LOGIN_ATTEMPTS) {
                $user->update(['is_locked' => true]);
                AuditLog::record('account.locked', $user, "{$user->username} locked after " . User::MAX_LOGIN_ATTEMPTS . ' failed attempts');
            }
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }

        $user->update(['login_attempt_count' => 0, 'last_login_at' => now()]);
        Auth::login($user);
        $request->session()->regenerate();
        if ($user->isStaff()) {
            AuditLog::record('auth.login', $user, "{$user->username} logged in");
        }

        return redirect()->intended($user->isStaff() ? route('dashboard') : route('catalog'));
    }

    public function register(Request $request)
    {
        $d = $request->validate([
            'first_name'     => 'required|regex:/^[\pL\s]+$/u|max:50',
            'last_name'      => 'required|regex:/^[\pL\s]+$/u|max:50',
            'username'       => 'required|alpha_num|max:50|unique:users,username',
            'email'          => 'required|email|max:255|unique:users,email',
            'contact_number' => 'required|numeric|digits_between:7,14',
            'address'        => 'required|max:100',
            'password'       => 'required|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($d) {
            $user = User::create([
                'username' => $d['username'],
                'name'     => $d['first_name'] . ' ' . $d['last_name'],
                'email'    => $d['email'],
                'password' => $d['password'], // hashed by model cast
                'role'     => User::CUSTOMER,
            ]);
            Customer::create([
                'user_id'        => $user->id,
                'first_name'     => $d['first_name'],
                'last_name'      => $d['last_name'],
                'contact_number' => $d['contact_number'],
                'address'        => $d['address'],
            ]);
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('catalog');
    }

    public function logout(Request $request)
    {
        if ($request->user()?->isStaff()) {
            AuditLog::record('auth.logout', $request->user(), "{$request->user()->username} logged out");
        }
        Auth::logout();
        $request->session()->invalidate();       // destroys session data (SRS 4.3.b)
        $request->session()->regenerateToken();

        return redirect()->route('catalog');
    }
}
