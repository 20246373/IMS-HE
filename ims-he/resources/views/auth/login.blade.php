@extends('layouts.app')
@section('title','Staff login')
@section('content')
<div class="card auth">
 <div class="auth-top"><a href="{{ route('login') }}">Change access</a><span>Staff</span></div>
 <h2>Staff login</h2>
 <form method="POST" action="{{ route('staff.login') }}">@csrf
  <label>Username <input name="username" value="{{ old('username') }}" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <p><button>Sign in</button></p>
 </form>
</div>
@endsection
