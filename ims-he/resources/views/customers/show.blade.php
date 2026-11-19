@extends('layouts.app')
@section('title', 'Customer profile')
@section('heading', 'Customer Profile')
@section('page-actions')
 <a class="btn" href="{{ route('customers.edit', $customer) }}">Edit profile</a>
 <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline"
       onsubmit="return confirm('Delete this customer account?')">
  @csrf @method('DELETE')
  <button type="submit" class="danger">Delete account</button>
 </form>
@endsection
@section('content')
<div class="card">
 <dl class="details">
  <div><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
  <div><dt>Email status</dt><dd>{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</dd></div>
  <div><dt>Name</dt><dd>{{ trim($customer->first_name.' '.$customer->last_name) ?: '—' }}</dd></div>
  <div><dt>Contact number</dt><dd>{{ $customer->contact_number ?: '—' }}</dd></div>
  <div><dt>Address</dt><dd>{{ $customer->address ?: '—' }}</dd></div>
 </dl>
</div>
<div class="card">
 <h3>Orders</h3>
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($orders as $order)
    <tr>
     <td>#{{ $order->invoice_id }}</td>
     <td>{{ $order->invoice_date->format('M d, Y') }}</td>
     <td>@include('orders._status', ['status' => $order->status])</td>
     <td>{{ money($order->total_amount) }}</td>
     <td>@if($order->channel === 'Online')<a class="btn" href="{{ route('online-orders.show', $order) }}">View</a>@endif</td>
    </tr>
   @empty
    <tr><td colspan="5">This customer has no orders.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection
