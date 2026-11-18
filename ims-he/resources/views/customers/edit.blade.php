@extends('layouts.app')
@section('title', 'Edit customer')
@section('heading'){{ trim($customer->first_name.' '.$customer->last_name) ?: $customer->email }}@endsection
@section('content')
<div class="card">
 <form method="POST" action="{{ route('customers.update', $customer) }}" class="plain-labels">
  @csrf
  @method('PUT')
  <div class="form-grid">
   <x-field label="Email" name="email" type="email" :value="$customer->email" maxlength="100" autocomplete="off" />
   <x-field label="First name" name="first_name" value="{{ $customer->first_name }}" maxlength="50" :required="false" />
   <x-field label="Last name" name="last_name" value="{{ $customer->last_name }}" maxlength="50" :required="false" />
   <x-field label="Contact number" name="contact_number" value="{{ $customer->contact_number }}" inputmode="numeric" maxlength="14" :required="false" />
   <x-field label="Address" name="address" value="{{ $customer->address }}" maxlength="100" :required="false" />
  </div>
  <button type="submit">Save changes</button>
  <a class="btn btn-alt" href="{{ route('customers.show', $customer) }}">Cancel</a>
 </form>
</div>
@endsection
