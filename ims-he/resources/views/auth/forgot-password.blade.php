@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="card auth">
 <a class="auth-back" href="{{ route('login') }}">Back</a>
 <h2>Forgot password</h2>
 <form method="POST" action="{{ route('password.email') }}">
  @csrf
  <div class="form-grid">
   <x-field label="Email" name="email" type="email" maxlength="100" autocomplete="email" autofocus />
  </div>
  <p><button>Send reset link</button></p>
 </form>
</div>
@endsection
