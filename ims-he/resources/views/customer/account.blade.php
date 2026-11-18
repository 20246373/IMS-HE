@extends('layouts.app')
@section('title','My account')
@section('heading', 'My Account')
@section('content')
<div class="card" style="max-width:560px">
 <table>
  <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
  <tr><th>Member since</th><td>{{ $customer->created_at?->format('M j, Y') }}</td></tr>
 </table>
</div>

<div class="card" style="max-width:560px">
 <h3>My profile</h3>
 <p><small>Optional for now. You will need an address and a contact number when you check out.</small></p>
 <form method="POST" action="{{ route('customer.account.update') }}">@csrf @method('PATCH')
  <label>First name <input name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="50"></label>
  <label>Last name <input name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="50"></label>
  <label>Contact number <input name="contact_number" value="{{ old('contact_number', $customer->contact_number) }}" maxlength="14" inputmode="numeric" pattern="[0-9]*" title="Digits only"></label>
  <label>Address <input name="address" value="{{ old('address', $customer->address) }}" maxlength="100"></label>
  <p><button>Save profile</button></p>
 </form>
</div>
@endsection
