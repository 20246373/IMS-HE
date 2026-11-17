@extends('layouts.app')
@section('title', 'Reset password')
@section('content')
<div class="card auth">
 <h2>Reset password</h2>
 <form method="POST" action="{{ route('password.reset', compact('guard', 'id', 'hash')) }}">
  @csrf
  <div class="form-grid">
   <x-password label="New password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" />
   <x-password label="Confirm new password" name="password_confirmation" autocomplete="new-password" />
  </div>
  <p><button>Reset password</button></p>
 </form>
</div>
@endsection
