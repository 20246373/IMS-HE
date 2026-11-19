@extends('layouts.app')
@section('title','Customer sign up')
@section('content')
<div class="card auth">
 <a class="auth-back" href="{{ route('register') }}">Change access</a>
 <h2>Customer sign up</h2>
 <p class="auth-sub">Add your name, contact number and address later on your profile or at checkout.</p>
 <form method="POST" action="{{ route('customer.register') }}">@csrf
  <div class="form-grid">
   <x-field label="Email" name="email" type="email" value="{{ old('email') }}" maxlength="100" autocomplete="email" autofocus />
   <x-password id="customer-password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" />
  </div>
  <p><button>Sign up</button></p>
 </form>
 <div class="auth-links">
  <span>Already have an account? <a href="{{ route('login', ['as' => 'customer']) }}">Log in</a></span>
 </div>
</div>
@endsection
