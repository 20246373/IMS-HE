@extends('layouts.app')
@section('title','Login')
@section('content')
<div class="card auth">
 <div class="logo">IH</div>
 <h2>Welcome back</h2>
 <form method="POST" action="{{ route('login') }}">@csrf
  <label>Username <input name="username" value="{{ old('username') }}" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <button class="full">Log in</button>
 </form>
 <p style="text-align:center;font-size:.9rem;margin-bottom:0">New here? <a href="{{ route('register') }}">Create an account</a></p>
</div>
@endsection
