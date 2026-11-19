@extends('layouts.app')
@section('title','Employee sign up')
@section('content')
<div class="card auth">
 <a class="auth-back" href="{{ route('register') }}">Change access</a>
 <h2>Employee sign up</h2>
 <form method="POST" action="{{ route('staff.register') }}">@csrf
  <div class="form-grid">
   <x-field label="First name" name="first_name" value="{{ old('first_name') }}" maxlength="50" autocomplete="given-name" autofocus />
   <x-field label="Last name" name="last_name" value="{{ old('last_name') }}" maxlength="50" autocomplete="family-name" />
   <x-field label="Work email" name="email" type="email" value="{{ old('email') }}" maxlength="100" autocomplete="email" />
   <x-password id="employee-password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" />
  </div>
  <p><button>Sign up</button></p>
 </form>
 <div class="auth-links">
  <span>Already have an account? <a href="{{ route('login', ['as' => 'employee']) }}">Log in</a></span>
 </div>
</div>
@endsection
