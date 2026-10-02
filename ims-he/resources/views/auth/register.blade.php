@extends('layouts.app')
@section('title','Register')
@section('content')
<div class="card" style="max-width:480px;margin:auto">
 <h2>Create customer account</h2>
 <form method="POST" action="{{ route('register') }}">@csrf
  <label>First name <input name="first_name" value="{{ old('first_name') }}" required></label>
  <label>Last name <input name="last_name" value="{{ old('last_name') }}" required></label>
  <label>Username <input name="username" value="{{ old('username') }}" required></label>
  <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
  <label>Contact number <input name="contact_number" value="{{ old('contact_number') }}" required></label>
  <label>Address <input name="address" value="{{ old('address') }}" required></label>
  <label>Password (min 8) <input type="password" name="password" required></label>
  <label>Confirm password <input type="password" name="password_confirmation" required></label>
  <p><button>Register</button></p>
 </form>
</div>
@endsection
