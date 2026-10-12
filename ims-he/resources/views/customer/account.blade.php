@extends('layouts.app')
@section('title','My account')
@section('content')
<div class="card">
 <h2>My account</h2>
 <table>
  <tr><th>Name</th><td>{{ $customer->first_name }} {{ $customer->last_name }}</td></tr>
  <tr><th>Username</th><td>{{ $customer->username }}</td></tr>
  <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
  <tr><th>Contact number</th><td>{{ $customer->contact_number }}</td></tr>
  <tr><th>Address</th><td>{{ $customer->address }}</td></tr>
  <tr><th>Member since</th><td>{{ $customer->created_at?->format('M j, Y') }}</td></tr>
 </table>
</div>
@endsection
