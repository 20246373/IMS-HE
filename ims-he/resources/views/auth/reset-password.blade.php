@extends('layouts.app')
@section('title', 'Reset password')
@section('content')
<div class="card auth">
 <h2>Reset password</h2>
 <form method="POST" action="{{ route('password.reset', compact('guard', 'id', 'hash')) }}">
  @csrf
  <x-required-note />
  <x-field label="New password" name="password" type="password" autocomplete="new-password" />
  <x-field label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" />
  <p><button>Reset password</button></p>
 </form>
</div>
@endsection
