<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Throwable;

// One login for everybody, like an online shop: the email decides whether it is a staff or a customer account.
// Employees sign up with email + password too, but get the role "Pending" until the Administrator assigns a real one.
class AuthController extends Controller
{
    private const INVALID = 'Invalid email or password.';

    private const NEW_ACCOUNT = 'This account is new and still uses its default password. Log in with the default password your administrator gave you, then you must change your password.';

    private const LOCKED = 'Account locked after 3 failed attempts. Contact the administrator.';

    /**
     * Without ?as= the page asks who is logging in; with ?as=customer or ?as=employee it shows the form.
     * Both forms post to the same login(), which finds the account by email.
     */
    public function showLogin(Request $request)
    {
        $as = in_array($request->query('as'), ['customer', 'employee'], true) ? $request->query('as') : null;

        return view('auth.login', ['as' => $as]);
    }

    /** Asks whether the visitor is signing up as a customer or as an employee. */
    public function chooseRegister()
    {
        return view('auth.register-choose');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:100']]);
        $email = mb_strtolower(trim($data['email']));
        $customer = Customer::where('email', $email)->first();
        $user = $customer ? null : User::where('email', $email)->first();

        if ($customer || $user) {
            $guard = $customer ? 'customer' : 'web';
            $id = $customer ? $customer->customer_id : $user->user_id;
            $passwordHash = $customer ? $customer->password : $user->password;
            $url = URL::temporarySignedRoute('password.reset', now()->addMinutes(60), [
                'guard' => $guard,
                'id' => $id,
                'hash' => hash('sha256', $passwordHash),
            ]);

            try {
                Mail::to($email)->send(new ResetPasswordEmail($url));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', 'If that email exists, we sent a link.');
    }

    public function showResetPassword(Request $request, string $guard, int $id, string $hash)
    {
        $account = $this->validResetAccount($request, $guard, $id, $hash);
        if (! $account) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        return view('auth.reset-password', compact('guard', 'id', 'hash'));
    }

    public function resetPassword(Request $request, string $guard, int $id, string $hash)
    {
        $account = $this->validResetAccount($request, $guard, $id, $hash);
        if (! $account) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
        $account->update(['password' => $data['password']]);

        if ($guard === 'web') {
            AuditLog::record('Password Reset', 'users', $account->user_id, $account->user_id);
        } else {
            AuditLog::recordByCustomer('Password Reset', 'customers', $account->customer_id, $account->customer_id);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $email = mb_strtolower(trim($data['login']));

        $user = User::where('email', $email)->first();
        if ($user) {
            return $this->loginStaff($request, $user, $data['password']);
        }

        $customer = Customer::where('email', $email)->first();
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

            // A new account still on its default password gets a hint instead of the usual error.
            // (The lockout above still applies; the default-password rule itself is not shown here.)
            return back()->withErrors(['login' => $user->must_change_password ? self::NEW_ACCOUNT : self::INVALID])->onlyInput('login');
        }

        $user->update(['login_attempt_count' => 0, 'last_login' => now()]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        AuditLog::record('Login', 'users', $user->user_id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

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
            return back()
                ->withErrors(['login' => 'Please verify your email address first. Check your inbox for the link, or resend it below.'])
                ->with('unverified_email', $customer->email)
                ->onlyInput('login');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        AuditLog::recordByCustomer('Login', 'customers', $customer->customer_id, $customer->customer_id);

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
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email'), Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],   // bcrypt reads only 72 bytes
        ], ['email.unique' => 'This email is already registered.', 'password.confirmed' => 'The two passwords do not match.']);

        $user = User::create([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
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

    /** First login of an account the Administrator created: the default password must be replaced. */
    public function showChangePassword(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate(
            ['password' => ['required', 'string', 'min:8', 'max:72', 'confirmed']],
            ['password.confirmed' => 'The two passwords do not match.'],
        );

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Choose a new password, not the default one.']);
        }

        $user->update(['password' => $data['password'], 'must_change_password' => false]);
        AuditLog::record('Password Changed', 'users', $user->user_id);

        return redirect()->route($user->isPending() ? 'staff.pending' : 'dashboard')
            ->with('status', 'Your password has been changed.');
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

    private function validResetAccount(Request $request, string $guard, int $id, string $hash)
    {
        if (! $request->hasValidSignature() || ! in_array($guard, ['customer', 'web'], true)) {
            return null;
        }

        $account = $guard === 'customer'
            ? Customer::find($id)
            : User::find($id);

        if (! $account || ! hash_equals(hash('sha256', $account->password), $hash)) {
            return null;
        }

        return $account;
    }
}
