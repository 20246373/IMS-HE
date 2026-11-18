@extends('layouts.app')
@section('title','Sign up')
@section('content')
<div class="card auth">
 <h2>Create your account</h2>
 <p class="auth-sub">Join Ibay Enterprises. Choose how you'll use IMS-HE.</p>
 <p class="choice-label">Sign up as</p>
 @include('partials.auth-choices', ['customerUrl' => route('customer.register'), 'employeeUrl' => route('staff.register')])
 <div class="auth-links">
  <span>Already have an account? <a href="{{ route('login') }}">Log in</a></span>
 </div>
</div>
@endsection
