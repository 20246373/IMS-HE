<?php

namespace App\Http\Controllers;

use App\Mail\VerifyCustomerEmail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

// Customer Account Module (SDD 4.1): storefront sign up on the 'customer' guard. Login is shared (AuthController).
// Sign up needs only an email and a password; name, phone and address are added later on "My account".
// UC02: a new account must verify its email (signed link) before it can log in (SDD 5).
// In local dev MAIL_MAILER=log writes the email to storage/logs/laravel.log and the link is also shown on the login page.
class CustomerAuthController extends Controller
{
    private const NAME_RULES = ['nullable', 'string', 'max:50', 'regex:/^[\pL ]+$/u'];

    public function showRegister()
    {
        return view('customer.register');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('customers', 'email'), Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:72'],        // bcrypt reads only 72 bytes
        ], ['email.unique' => 'This email is already registered.']);

        $customer = Customer::create($data);      // password is bcrypt-hashed by the model's 'hashed' cast

        $sent = $this->sendVerification($customer);

        // UC02 includes UC01: registration finishes by sending the customer to the login page.
        return redirect()->route('login')->with('status', $sent
            ? 'Account created. We sent a verification link to '.$customer->email.'. Verify your email, then log in.'
            : 'Account created, but the verification email could not be sent. Use "Resend verification email" below.');
    }

    public function verify(Request $request, Customer $customer, string $hash)
    {
        // A bad, tampered or expired link gets a clear message and a way to ask for a new one.
        if (! $request->hasValidSignature() || ! hash_equals(sha1($customer->email), $hash)) {
            return redirect()->route('login')->withErrors(['login' => 'This verification link is invalid or has expired. Enter your email below to get a new one.']);
        }

        if ($customer->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Your email is already verified. Please log in.');
        }

        $customer->markEmailAsVerified();

        // First successful click: the customer is signed in right away, like most online shops.
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Email verified. Welcome!');
    }

    public function resend(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:100']);

        $customer = Customer::where('email', mb_strtolower(trim($data['email'])))->first();
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

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'first_name' => self::NAME_RULES,
            'last_name' => self::NAME_RULES,
            'contact_number' => ['nullable', 'regex:/^[0-9]+$/', 'max:14'],
            'address' => ['nullable', 'string', 'max:100'],
        ], [
            'first_name.regex' => 'First name may only contain letters and spaces.',
            'last_name.regex' => 'Last name may only contain letters and spaces.',
            'contact_number.regex' => 'Contact number may only contain digits.',
        ]);

        $request->user('customer')->update($data);

        return back()->with('status', 'Profile updated.');
    }
}
