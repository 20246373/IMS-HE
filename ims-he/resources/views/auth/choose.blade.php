@extends('layouts.app')
@section('title','Log in')
@section('content')
<div class="card auth">
 <h2>Log in</h2>
 <p>Select how you want to sign in:</p>
 <a class="btn" href="{{ route('staff.login') }}">Staff</a>
 <a class="btn" href="{{ route('customer.login') }}">Customer</a>
 <p>New customer?</p>
 <a class="btn btn-alt" href="{{ route('customer.register') }}">Create a customer account</a>
</div>
@endsection
