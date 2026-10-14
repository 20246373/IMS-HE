<?php

namespace App\Http\Controllers;

use App\Mail\VerifyCustomerEmail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Customer Account Module (SDD 4.1): storefront sign up / login on the 'customer' guard.
// UC02: a new account must verify its email (signed link) before it can log in (SDD 5).
// In local dev MAIL_MAILER=log writes the email to storage/logs/laravel.log and the link is also shown on the login page.
class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer.register');
    }

    public function register(Request $request)
    {
        // Rules follow the data dictionary, SDD 7.2.2
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u'],
            'username' => ['required', 'alpha_num:ascii', 'max:50', 'unique:customers,username'],
            'email' => ['required', 'email', 'max:100', 'unique:customers,email'],
            'contact_number' => ['required', 'regex:/^[0-9]+$/', 'max:14'],
            'address' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],   // bcrypt reads only 72 bytes
        ], [
            'first_name.regex' => 'First name may only contain letters and spaces.',
            'last_name.regex' => 'Last name may only contain letters and spaces.',
            'contact_number.regex' => 'Contact number may only contain digits.',
        ]);

        $customer = Customer::create($data);      // password is bcrypt-hashed by the model's 'hashed' cast

        $sent = $this->sendVerification($customer);

        // UC02 includes UC01: registration finishes by sending the customer to the login flow.
        return redirect()->route('customer.login')->with('status', $sent
            ? 'Account created. We sent a verification link to '.$customer->email.'. Verify your email, then log in.'
            : 'Account created, but the verification email could not be sent. Use "Resend verification email" below.');
    }

    public function showLogin()
    {
        return view('customer.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // SDD 6: customers may log in with either username or email.
        $customer = Customer::where('username', $data['login'])->orWhere('email', $data['login'])->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            return back()->withErrors(['login' => 'Invalid username/email or password.'])->onlyInput('login');
        }

        if (! $customer->hasVerifiedEmail()) {
            return back()->withErrors(['login' => 'Please verify your email address first. Check your inbox for the link, or resend it below.'])->onlyInput('login');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function verify(Customer $customer, string $hash)
    {
        abort_unless(hash_equals(sha1($customer->email), $hash), 403);

        if (! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
        }

        return redirect()->route('customer.login')->with('status', 'Email verified. You can now log in.');
    }

    public function resend(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:100']);

        $customer = Customer::where('email', $data['email'])->first();
        if ($customer && ! $customer->hasVerifiedEmail()) {
            $this->sendVerification($customer);
        }

        // Same answer either way so the form cannot be used to find out which emails are registered.
        return back()->with('status', 'If that email belongs to an unverified account, a new verification link has been sent.');
    }

    private function sendVerification(Customer $customer): bool
    {
        $url = $customer->verificationUrl();

        if (app()->environment('local')) {
            session()->flash('dev_verification_url', $url);   // dev convenience only
        }

        try {
            Mail::to($customer->email)->send(new VerifyCustomerEmail($customer, $url));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function account(Request $request)
    {
        return view('customer.account', ['customer' => $request->user('customer')]);
    }
}
