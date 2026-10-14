<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])->first();
        $invalid = 'Invalid username or password.';

        if (! $user) {
            return back()->withErrors(['username' => $invalid])->onlyInput('username');
        }

        if ($user->isLocked()) {
            return back()->withErrors(['username' => 'Account locked after 3 failed attempts. Contact the President.'])->onlyInput('username');
        }

        if (! Hash::check($data['password'], $user->password)) {
            $user->increment('login_attempt_count');

            if ($user->login_attempt_count >= User::MAX_LOGIN_ATTEMPTS) {
                $user->update(['status' => User::STATUS_LOCKED]);
                AuditLog::record('Locked', 'users', $user->user_id, $user->user_id);

                return back()->withErrors(['username' => 'Account locked after 3 failed attempts. Contact the President.'])->onlyInput('username');
            }

            return back()->withErrors(['username' => $invalid])->onlyInput('username');
        }

        $user->update(['login_attempt_count' => 0, 'last_login' => now()]);

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('Login', 'users', $user->user_id);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLog::record('Logout', 'users', Auth::id());

        Auth::logout();
        $request->session()->invalidate();        // destroys session data (SRS 4.3.b)
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
