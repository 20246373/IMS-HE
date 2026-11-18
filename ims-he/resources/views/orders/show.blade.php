@extends('layouts.app')
@section('title','Receipt #'.$invoice->invoice_id)
@section('heading')Receipt &mdash; Order #{{ $invoice->invoice_id }}@endsection
@section('content')
<div class="card order-document" id="receipt">
 <h2 id="receipt-company" class="receipt-company">Ibay Enterprises Corporation</h2>
 <div class="order-document-content">
  <section aria-labelledby="receipt-company">
   <dl class="order-document-details">
    <div><dt>Order number</dt><dd>#{{ $invoice->invoice_id }}</dd></div>
    <div><dt>Date</dt><dd>{{ $invoice->invoice_date->format('M d, Y') }}</dd></div>
    <div><dt>Number of items</dt><dd>{{ $invoice->items->sum('quantity') }}</dd></div>
    <div><dt>Status</dt><dd>@include('orders._status', ['status' => $invoice->status])</dd></div>
    <div><dt>Payment method</dt><dd>{{ $invoice->payment?->payment_method }}</dd></div>
   </dl>
  </section>
  <section class="order-document-aside" aria-labelledby="receipt-delivery">
   <h2 id="receipt-delivery">Deliver to</h2>
   <p>
    <strong>{{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }}</strong><br>
    {{ $invoice->customer->contact_number }}<br>
    {{ $invoice->customer->address }}
   </p>
  </section>
 </div>

 <table class="cart-table receipt-items">
  <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
  @foreach($invoice->items as $line)
   <tr>
    <td>{{ $line->product->product_name }}</td>
    <td>{{ money($line->price) }}</td>
    <td>{{ $line->quantity }}</td>
    <td>{{ money($line->subtotal) }}</td>
   </tr>
  @endforeach
  <tr class="cart-total-row"><th colspan="3">Total</th><th>{{ money($invoice->total_amount) }}</th></tr>
 </table>

 @if($invoice->status === 'Cancelled')
  <p><strong>This order was cancelled.</strong> No payment is due.</p>
 @else
  <p><small>Thank you for your order. The store will contact you to arrange payment and pickup or delivery.</small></p>
 @endif

 <div class="order-document-actions no-print">
  <button type="button" onclick="window.print()">Print receipt</button>
  <a class="btn btn-alt" href="{{ route('customer.orders.index') }}">My orders</a>
  <a class="btn btn-alt" href="{{ route('catalog.index') }}">Continue shopping</a>
 </div>
</div>
@push('head')
<style>@media print { nav, .no-print, [role=alert] { display: none !important; } body { background: #fff; } main { margin: 0 !important; } #receipt { box-shadow: none; max-width: none !important; } .status { border: 1px solid currentColor; } }</style>
@endpush
@endsection
