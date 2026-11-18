@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div style="max-width: 440px; margin: 2rem auto 0 auto;">
    <div class="checkout-card">
        <h3 style="margin-top: 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; font-size: 1.15rem; color: #111827;">
            New Customer Registration
        </h3>
        
        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1.25rem;">
            Sign up with your email and password. Contact details and address can be added later on your profile or during checkout.
        </p>

        @if ($errors->any())
            <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 0.85rem; border-radius: 6px; margin-bottom: 1rem; color: #991b1b; font-size: 0.875rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register') }}" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf

            <div>
                <label for="email" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                    Email Address <span style="color:#e11d48;">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
            </div>

            <div>
                <label for="password" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                    Password <span style="color:#e11d48;">*</span> (min. 8 characters)
                </label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                    style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn-primary" style="padding: 0.75rem; font-size: 1rem; font-weight: 700; margin-top: 0.5rem;">
                Create Account &rarr;
            </button>
        </form>

        <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center; font-size: 0.875rem;">
            <span style="color: #6b7280;">Already have an account?</span>
            <a href="{{ route('login') }}" style="color: #1f3a5f; font-weight: 600; margin-left: 0.35rem;">Log In</a>
        </div>
    </div>
</div>
@endsection