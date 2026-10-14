@extends('layouts.app')
@section('title','Sign up')
@section('content')
<div class="card auth" style="max-width:460px">
 <div class="auth-top"><a href="{{ route('login') }}">Change access</a><span>Customer</span></div>
 <h2>Create a customer account</h2>
 <form method="POST" action="{{ route('customer.register') }}">@csrf
  <label>First name <input name="first_name" value="{{ old('first_name') }}" maxlength="50" required autofocus></label>
  <label>Last name <input name="last_name" value="{{ old('last_name') }}" maxlength="50" required></label>
  <label>Username <input name="username" value="{{ old('username') }}" maxlength="50" pattern="[A-Za-z0-9]+" title="Letters and numbers only" required></label>
  <label>Email <input type="email" name="email" value="{{ old('email') }}" maxlength="100" required></label>
  <label>Contact number <input name="contact_number" value="{{ old('contact_number') }}" maxlength="14" inputmode="numeric" pattern="[0-9]+" title="Digits only" required></label>
  <label>Address <input name="address" value="{{ old('address') }}" maxlength="100" required></label>
  <label>Password <input type="password" name="password" minlength="8" required></label>
  <label>Confirm password <input type="password" name="password_confirmation" minlength="8" required></label>
  <p><button>Sign up</button></p>
 </form>
 <p>Already have an account? <a href="{{ route('customer.login') }}">Log in</a></p>
</div>
@endsection
