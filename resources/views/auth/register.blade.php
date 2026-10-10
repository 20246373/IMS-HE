@extends('layouts.app')
@section('title','Register')
@section('content')
<div class="card auth wide">
 <div class="logo">IH</div>
 <h2>Create your account</h2>
 <form method="POST" action="{{ route('register') }}">@csrf
  <div class="cols2">
   <label>First name <input name="first_name" value="{{ old('first_name') }}" required></label>
   <label>Last name <input name="last_name" value="{{ old('last_name') }}" required></label>
   <label>Username <input name="username" value="{{ old('username') }}" required></label>
   <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
   <label>Contact number <input name="contact_number" value="{{ old('contact_number') }}" required></label>
   <label>Address <input name="address" value="{{ old('address') }}" required></label>
   <label>Password (min 8) <input type="password" name="password" required></label>
   <label>Confirm password <input type="password" name="password_confirmation" required></label>
  </div>
  <button class="full">Sign up</button>
 </form>
 <p style="text-align:center;font-size:.9rem;margin-bottom:0">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
