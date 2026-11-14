@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="card auth">
 <h2>Forgot password</h2>
 <p>Enter your email and we will send a password reset link if an account exists.</p>
 <form method="POST" action="{{ route('password.email') }}">
  @csrf
  <x-required-note />
  <x-field label="Email" name="email" type="email" maxlength="100" autocomplete="email" autofocus />
  <p><button>Send reset link</button></p>
 </form>
 <p><a href="{{ route('login') }}">Back to login</a></p>
</div>
@endsection
