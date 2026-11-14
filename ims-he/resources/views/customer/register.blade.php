@extends('layouts.app')
@section('title','Sign up')
@section('content')
<div class="card auth">
 <h2>Sign up</h2>
 <form method="POST" action="{{ route('customer.register') }}">@csrf
  <x-required-note />
  <div class="form-grid">
   <x-field label="Email" name="email" type="email" value="{{ old('email') }}" maxlength="100" autocomplete="email" autofocus />
   <x-field label="Password (at least 8 characters)" name="password" type="password" id="signup-password" minlength="8" maxlength="72" autocomplete="new-password" />
  </div>
  <label class="optional" style="font-weight:400"><input type="checkbox" style="display:inline;width:auto" onclick="document.getElementById('signup-password').type = this.checked ? 'text' : 'password'"> Show password</label>
  <p><button>Sign up</button></p>
 </form>
 <p>Have an account? <a href="{{ route('login') }}">Log in</a></p>
 <p><small>Work here? <a href="{{ route('staff.register') }}">Request employee access</a></small></p>
</div>
@endsection
