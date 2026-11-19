@extends('layouts.app')
@section('title', 'Edit customer account')
@section('heading'){{ trim($customer->first_name.' '.$customer->last_name) ?: $customer->email }}@endsection
@section('content')
<div class="split even">
 <div class="card">
  <h3>Account details</h3>
  <form method="POST" action="{{ route('customer-accounts.update', $customer) }}" class="plain-labels">@csrf @method('PUT')
   <x-required-note />
   <div class="form-grid">
    <x-field label="First name" name="first_name" :value="$customer->first_name" maxlength="50" :required="false" autocomplete="off" />
    <x-field label="Last name" name="last_name" :value="$customer->last_name" maxlength="50" :required="false" autocomplete="off" />
    <x-field label="Email" name="email" type="email" :value="$customer->email" maxlength="100" autocomplete="off" />
    <x-value label="Email status">{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</x-value>
   </div>
   <div class="actions"><button>Save</button> <a class="btn danger" href="{{ route('customer-accounts.index') }}">Cancel</a></div>
  </form>
 </div>
 <div class="card">
  <h3>Reset password</h3>
  <form method="POST" action="{{ route('customer-accounts.password', $customer) }}" class="plain-labels">@csrf
   <div class="form-grid single">
    <x-password label="New password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" />
    <x-password label="Confirm password" name="password_confirmation" autocomplete="new-password" />
   </div>
   <div class="actions"><button>Reset password</button></div>
  </form>
 </div>
</div>
@endsection
