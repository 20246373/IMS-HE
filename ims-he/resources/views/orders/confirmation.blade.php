@extends('layouts.app')
@section('title','Order placed')
@section('content')
<div class="card" style="max-width:640px">
 <x-alert type="success" title="Order placed">Thank you! Your order #{{ $invoice->invoice_id }} has been received.</x-alert>

 <table>
  <tr><th>Order number</th><td>#{{ $invoice->invoice_id }}</td></tr>
  <tr><th>Date</th><td>{{ $invoice->invoice_date->format('M d, Y') }}</td></tr>
  <tr><th>Items</th><td>{{ $invoice->items->sum('quantity') }}</td></tr>
  <tr><th>Total</th><td><strong>{{ money($invoice->total_amount) }}</strong></td></tr>
  <tr><th>Payment method</th><td>{{ $invoice->payment?->payment_method }}</td></tr>
  <tr><th>Status</th><td>@include('orders._status', ['status' => $invoice->status])</td></tr>
 </table>

 <h3>What happens next</h3>
 <p>The store will contact you at <strong>{{ $invoice->customer->contact_number }}</strong> to arrange payment and pickup or delivery to <strong>{{ $invoice->customer->address }}</strong>. You can follow the status in My orders.</p>

 <p>
  <a class="btn" href="{{ route('customer.orders.show', $invoice) }}">View receipt</a>
  <a class="btn btn-alt" href="{{ route('customer.orders.index') }}">My orders</a>
  <a href="{{ route('catalog.index') }}">Continue shopping</a>
 </p>
</div>
@endsection
