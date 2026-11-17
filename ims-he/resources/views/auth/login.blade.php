@extends('layouts.app')
@section('title','Log in')
@section('content')
<div class="card auth">
 @if(! $as)
  {{-- Step 1: who is logging in --}}
  <h2>Log in</h2>
  <p class="choice-label">Log in as</p>
  <div class="choice-list">
   <a href="{{ route('login', ['as' => 'customer']) }}">Customer</a>
   <a href="{{ route('login', ['as' => 'employee']) }}">Employee</a>
  </div>
  <div class="auth-links">
   <span>Don't have an account? <a href="{{ route('register') }}">Sign up</a></span>
  </div>
 @else
  {{-- Step 2: the form. Both post to the same login, which finds the account by email or username. --}}
  <a class="auth-back" href="{{ route('login') }}">Change access</a>
  <h2>{{ $as === 'customer' ? 'Customer log in' : 'Employee log in' }}</h2>
  <form method="POST" action="{{ route('login') }}">@csrf
   <div class="form-grid">
    @if($as === 'customer')
     <x-field label="Email" name="login" type="email" value="{{ old('login') }}" maxlength="100" autocomplete="email" autofocus />
    @else
     <x-field label="Email or username" name="login" value="{{ old('login') }}" maxlength="100" autocomplete="username" autofocus />
    @endif
    <x-password id="login-password" autocomplete="current-password" />
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
