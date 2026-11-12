<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// One login for everybody, like an online shop: the email (or username) decides whether it is a staff or a customer account.
// Employees sign up with email + password too, but get the role "Pending" until the President assigns a real one.
class AuthController extends Controller
{
    private const INVALID = 'Invalid email/username or password.';
    private const LOCKED = 'Account locked after 3 failed attempts. Contact the President.';

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $id = trim($data['login']);
        $email = mb_strtolower($id);

        $user = User::where('email', $email)->first() ?? User::where('username', $id)->first();
        if ($user) {
            return $this->loginStaff($request, $user, $data['password']);
        }

        $customer = Customer::where('email', $email)->first() ?? Customer::where('username', $id)->first();
        if ($customer) {
            return $this->loginCustomer($request, $customer, $data['password']);
        }

        return back()->withErrors(['login' => self::INVALID])->onlyInput('login');
    }

    private function loginStaff(Request $request, User $user, string $password)
    {
        if ($user->isLocked()) {
            return back()->withErrors(['login' => self::LOCKED])->onlyInput('login');
        }

        if (! Hash::check($password, $user->password)) {
            $user->increment('login_attempt_count');

            if ($user->login_attempt_count >= User::MAX_LOGIN_ATTEMPTS) {
                $user->update(['status' => User::STATUS_LOCKED]);
                AuditLog::record('Locked', 'users', $user->user_id, $user->user_id);

                return back()->withErrors(['login' => self::LOCKED])->onlyInput('login');
            }

            return back()->withErrors(['login' => self::INVALID])->onlyInput('login');
        }

        $user->update(['login_attempt_count' => 0, 'last_login' => now()]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        AuditLog::record('Login', 'users', $user->user_id);

        return $user->isPending()
            ? redirect()->route('staff.pending')
            : redirect()->intended(route('dashboard'));
    }

    private function loginCustomer(Request $request, Customer $customer, string $password)
    {
        if (! Hash::check($password, $customer->password)) {
            return back()->withErrors(['login' => self::INVALID])->onlyInput('login');
        }

        if (! $customer->hasVerifiedEmail()) {
            return back()->withErrors(['login' => 'Please verify your email address first. Check your inbox for the link, or resend it below.'])->onlyInput('login');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        return view('auth.register-employee');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email'), Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:72'],        // bcrypt reads only 72 bytes
        ], ['email.unique' => 'This email is already registered.']);

        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],             // hashed by the model cast
            'role' => User::PENDING,
            'status' => User::STATUS_ACTIVE,
        ]);

        AuditLog::record('Registered', 'users', $user->user_id, $user->user_id);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('staff.pending');
    }

    public function pending(Request $request)
    {
        if (! $request->user()->isPending()) {
            return redirect()->route('dashboard');
        }

        return view('auth.pending');
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
