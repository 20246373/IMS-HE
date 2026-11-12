@extends('layouts.app')
@section('title','Employee sign up')
@section('content')
<div class="card auth">
 <h2>Employee sign up</h2>
 <p><small>Create your account with an email and a password. You can sign in right away, but the President has to give you a role before you can use any part of the system.</small></p>
 <form method="POST" action="{{ route('staff.register') }}">@csrf
  <label>Work email <input type="email" name="email" value="{{ old('email') }}" maxlength="100" autocomplete="email" required autofocus></label>
  <label>Password (at least 8 characters) <input type="password" name="password" id="employee-password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
  <label style="font-weight:400"><input type="checkbox" style="display:inline;width:auto" onclick="document.getElementById('employee-password').type = this.checked ? 'text' : 'password'"> Show password</label>
  <p><button>Request access</button></p>
 </form>
 <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
