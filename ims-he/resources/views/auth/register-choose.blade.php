@extends('layouts.app')
@section('title','Sign up')
@section('content')
<div class="card auth">
 <h2>Sign up</h2>
 <p class="choice-label">Sign up as</p>
 <div class="choice-list">
  <a href="{{ route('customer.register') }}">Customer</a>
  <a href="{{ route('staff.register') }}">Employee</a>
 </div>
 <div class="auth-links">
  <span>Already have an account? <a href="{{ route('login') }}">Log in</a></span>
 </div>
</div>
@endsection
