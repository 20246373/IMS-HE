@extends('layouts.app')
@section('title','Receipt #'.$invoice->invoice_id)
@section('content')
<div class="card" id="receipt" style="max-width:640px">
 <h2>Receipt &mdash; Order #{{ $invoice->invoice_id }}</h2>
 <p>
  <strong>Ibay Enterprises Corporation</strong><br>
  Date: {{ $invoice->invoice_date->format('M d, Y') }}<br>
  Status: <strong>{{ $invoice->status }}</strong>
 </p>
 <p>
  <strong>Deliver to</strong><br>
  {{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }}<br>
  {{ $invoice->customer->contact_number }}<br>
  {{ $invoice->customer->address }}
 </p>

 <table>
  <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
  @foreach($invoice->items as $line)
   <tr>
    <td>{{ $line->product->product_name }}</td>
    <td>&#8369;{{ number_format($line->price, 2) }}</td>
    <td>{{ $line->quantity }}</td>
    <td>&#8369;{{ number_format($line->subtotal, 2) }}</td>
   </tr>
  @endforeach
  <tr><th colspan="3">Total</th><th>&#8369;{{ number_format($invoice->total_amount, 2) }}</th></tr>
 </table>

 <p>Payment method: <strong>{{ $invoice->payment?->payment_method }}</strong></p>
 <p><small>Thank you for your order. The store will contact you to arrange payment and pickup or delivery.</small></p>

 <p class="no-print">
  <button type="button" onclick="window.print()">Print receipt</button>
  <a class="btn" href="{{ route('customer.orders.index') }}">My orders</a>
  <a class="btn" href="{{ route('catalog.index') }}">Continue shopping</a>
 </p>
</div>
@push('head')
<style>@media print { nav, .no-print { display: none !important; } body { background: #fff; } #receipt { box-shadow: none; } }</style>
@endpush
@endsection
