<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Customer Account Module (SDD 4.1): storefront sign up / login on the 'customer' guard.
// Email verification (SDD 5) is not done yet; email_verified_at stays null.
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

        Customer::create($data);                  // password is bcrypt-hashed by the model's 'hashed' cast

        // UC02 includes UC01: registration finishes by sending the customer to the login flow.
        return redirect()->route('customer.login')->with('status', 'Account created. You can now log in.');
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

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
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
