@extends('layouts.app')
@section('title','Order placed')
@section('heading', 'Order Placed')
@section('content')
<div class="card order-document">
 <x-alert type="success" title="Order placed">Thank you! Your order #{{ $invoice->invoice_id }} has been received.</x-alert>

 <div class="order-document-content">
  <section aria-labelledby="confirmation-summary">
   <h2 id="confirmation-summary">Order summary</h2>
   <dl class="order-document-details">
    <div><dt>Order number</dt><dd>#{{ $invoice->invoice_id }}</dd></div>
    <div><dt>Date</dt><dd>{{ $invoice->invoice_date->format('M d, Y') }}</dd></div>
    <div><dt>Number of items</dt><dd>{{ $invoice->items->sum('quantity') }}</dd></div>
    <div><dt>Payment method</dt><dd>{{ $invoice->payment?->payment_method }}</dd></div>
    <div><dt>Status</dt><dd>@include('orders._status', ['status' => $invoice->status])</dd></div>
    <div><dt>Total</dt><dd><strong>{{ money($invoice->total_amount) }}</strong></dd></div>
   </dl>
  </section>
  <section class="order-document-aside" aria-labelledby="confirmation-next">
   <h2 id="confirmation-next">Reminder</h2>
   <p>The store will contact you at <strong>{{ $invoice->customer->contact_number }}</strong> to arrange payment and pickup or delivery to <strong>{{ $invoice->customer->address }}</strong>.</p>
   <p>You can check the status in my orders.</p>
  </section>
 </div>

 <div class="order-document-actions">
  <a class="btn" href="{{ route('customer.orders.show', $invoice) }}">View receipt</a>
  <a class="btn btn-alt" href="{{ route('customer.orders.index') }}">My orders</a>
  <a class="btn btn-alt" href="{{ route('catalog.index') }}">Continue shopping</a>
 </div>
</div>
@endsection
