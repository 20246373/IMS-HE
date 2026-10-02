@extends('layouts.app')
@section('title','Login')
@section('content')
<div class="card" style="max-width:380px;margin:auto">
 <h2>Login</h2>
 <form method="POST" action="{{ route('login') }}">@csrf
  <label>Username <input name="username" value="{{ old('username') }}" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <p><button>Sign in</button></p>
 </form>
</div>
@endsection
