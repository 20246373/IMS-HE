@extends('layouts.app')
@section('title','Customer login')
@section('content')
<div class="card auth">
 <div class="auth-top"><a href="{{ route('login') }}">&larr; Change access</a><span>Customer</span></div>
 <h2>Customer login</h2>
 <form method="POST" action="{{ route('customer.login') }}">@csrf
  <label>Username or email <input name="login" value="{{ old('login') }}" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <p><button>Sign in</button></p>
 </form>
 <p>No account yet? <a href="{{ route('customer.register') }}">Sign up</a></p>
</div>
@endsection
