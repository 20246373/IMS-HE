@extends('layouts.app')
@section('title','Log in')
@section('content')
<div class="card auth">
 @if(! $as)
  {{-- Step 1: who is logging in --}}
  <h2>Welcome</h2>
  <p class="auth-sub">Log in to continue to IMS-HE.</p>
  <p class="choice-label">Log in as</p>
  @include('partials.auth-choices', ['customerUrl' => route('login', ['as' => 'customer']), 'employeeUrl' => route('login', ['as' => 'employee'])])
  <div class="auth-links">
   <span>Don't have an account? <a href="{{ route('register') }}">Sign up</a></span>
  </div>
 @else
  {{-- Step 2: the form. Both post to the same login, which finds the account by email. --}}
  <a class="auth-back" href="{{ route('login') }}">Change access</a>
  <h2>Welcome back</h2>
  <p class="auth-sub">Log in to your {{ $as === 'customer' ? 'customer' : 'employee' }} account.</p>
  <form method="POST" action="{{ route('login') }}">@csrf
   <div class="form-grid">
    <x-field label="Email" name="login" type="email" value="{{ old('login') }}" maxlength="100" autocomplete="email" autofocus :inline-error="false" />
    <x-password id="login-password" autocomplete="current-password" :inline-error="false" />
   </div>
   <p><button>Log in</button></p>
  </form>

  {{-- Only shown after an unverified customer tries to log in. --}}
  @if(session('unverified_email'))
   <form method="POST" action="{{ route('customer.verification.resend') }}">@csrf
    <input type="hidden" name="email" value="{{ session('unverified_email') }}">
    <p><button class="btn-alt">Resend verification email</button></p>
   </form>
  @endif

  @if(session('dev_verification_url'))
   <p><small>Local dev only: <a href="{{ session('dev_verification_url') }}">open the verification link</a></small></p>
  @endif

  <div class="auth-links">
   <a href="{{ route('password.forgot') }}">Forgot your password?</a>
   <span>Don't have an account? <a href="{{ route($as === 'customer' ? 'customer.register' : 'staff.register') }}">Sign up</a></span>
  </div>
 @endif
</div>
@endsection