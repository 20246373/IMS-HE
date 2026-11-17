@extends('layouts.app')
@section('title', 'Customer profile')
@section('content')
<div class="card">
 <div class="report-heading"><h2>Customer profile</h2><a class="btn" href="{{ route('customers.edit', $customer) }}">Edit profile</a></div>
 <div class="form-grid">
  <p><strong>Email</strong><br>{{ $customer->email }}</p>
  <p><strong>Email status</strong><br>{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</p>
  <p><strong>Name</strong><br>{{ trim($customer->first_name.' '.$customer->last_name) ?: '—' }}</p>
  <p><strong>Contact number</strong><br>{{ $customer->contact_number ?: '—' }}</p>
  <p><strong>Address</strong><br>{{ $customer->address ?: '—' }}</p>
 </div>
</div>
<div class="card">
 <h2>Orders</h2>
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
  <tbody>
   @forelse($orders as $order)
    <tr>
     <td>#{{ $order->invoice_id }}</td>
     <td>{{ $order->invoice_date->format('M d, Y') }}</td>
     <td>@include('orders._status', ['status' => $order->status])</td>
     <td>{{ money($order->total_amount) }}</td>
     <td>@if($order->channel === 'Online')<a href="{{ route('online-orders.show', $order) }}">Open online order</a>@endif</td>
    </tr>
   @empty
    <tr><td colspan="5">This customer has no orders.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection
