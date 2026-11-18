@extends('layouts.app')
@section('title','My account')
@section('heading', 'My Account')
@section('content')
<div class="card account-panel">
 <dl class="account-details">
  <div class="account-row"><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
  <div class="account-row"><dt>Member since</dt><dd>{{ $customer->created_at?->format('M j, Y') }}</dd></div>
 </dl>
</div>

<div class="card account-panel">
 <h3>My profile</h3>
 <p><small>Optional for now. You will need an address and a contact number when you check out.</small></p>
 <form class="account-profile" method="POST" action="{{ route('customer.account.update') }}">@csrf @method('PATCH')
  <label class="account-row">First name <input name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="50"></label>
  <label class="account-row">Last name <input name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="50"></label>
  <label class="account-row">Contact number <input name="contact_number" value="{{ old('contact_number', $customer->contact_number) }}" maxlength="14" inputmode="numeric" pattern="[0-9]{7,14}" title="Digits only, 7 to 14 numbers"></label>
  <label class="account-row">Address <input name="address" value="{{ old('address', $customer->address) }}" maxlength="100"></label>
  <div class="account-row account-actions"><button>Save profile</button></div>
 </form>
</div>
@endsection
