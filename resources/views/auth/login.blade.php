@extends('layouts.app')
@section('title','Log in')
@section('content')
<div class="card auth">
 <h2>Log in</h2>
 <form method="POST" action="{{ route('login') }}">@csrf
  <label>Email or username <input name="login" value="{{ old('login') }}" maxlength="100" autocomplete="username" required autofocus></label>
  <label>Password <input type="password" name="password" id="login-password" autocomplete="current-password" required></label>
  <label style="font-weight:400"><input type="checkbox" style="display:inline;width:auto" onclick="document.getElementById('login-password').type = this.checked ? 'text' : 'password'"> Show password</label>
  <p><button>Log in</button></p>
 </form>
 <p>New here? <a href="{{ route('customer.register') }}">Sign up</a></p>
 <p><small>Work here? <a href="{{ route('staff.register') }}">Request employee access</a></small></p>

 @if(session('dev_verification_url'))
  <p><small>Local dev only: <a href="{{ session('dev_verification_url') }}">open the verification link</a></small></p>
 @endif

 <details>
  <summary><small>Did not get the verification email?</small></summary>
  <form method="POST" action="{{ route('customer.verification.resend') }}">@csrf
   <label>Your email <input type="email" name="email" maxlength="100" required></label>
   <p><button>Resend verification email</button></p>
  </form>
 </details>
</div>
@endsection
