@extends('layouts.app')
@section('title','Change your password')
@section('content')
<div class="card auth">
 <h2>Change your password</h2>
 <p class="auth-sub">Welcome, {{ auth()->user()->displayName() }}. Your account was set up with a default password. Choose your own password to continue.</p>
 <form method="POST" action="{{ route('password.change.update') }}">@csrf
  <div class="form-grid">
   <x-password label="New password" id="new-password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" autofocus />
   <x-password label="Confirm password" name="password_confirmation" id="new-password-confirm" autocomplete="new-password" />
  </div>
  <p><button>Save password</button></p>
 </form>
</div>
@endsection
