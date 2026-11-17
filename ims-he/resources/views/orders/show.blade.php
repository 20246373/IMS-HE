@extends('layouts.app')
@section('title','Receipt #'.$invoice->invoice_id)
@section('content')
<div class="card" id="receipt" style="max-width:640px">
 <h2>Receipt &mdash; Order #{{ $invoice->invoice_id }}</h2>
 <p>
  <strong>Ibay Enterprises Corporation</strong><br>
  Date: {{ $invoice->invoice_date->format('M d, Y') }}<br>
  Status: @include('orders._status', ['status' => $invoice->status])
 </p>
 <p>
  <strong>Deliver to</strong><br>
  {{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }}<br>
  {{ $invoice->customer->contact_number }}<br>
  {{ $invoice->customer->address }}
 </p>

 <table class="cart-table">
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

 <p>Payment method: <strong>{{ $invoice->payment?->payment_method }}</strong></p>
 @if($invoice->status === 'Cancelled')
  <p><strong>This order was cancelled.</strong> No payment is due.</p>
 @else
  <p><small>Thank you for your order. The store will contact you to arrange payment and pickup or delivery.</small></p>
 @endif

 <p class="no-print">
  <button type="button" onclick="window.print()">Print receipt</button>
  <a class="btn" href="{{ route('customer.orders.index') }}">My orders</a>
  <a class="btn btn-alt" href="{{ route('catalog.index') }}">Continue shopping</a>
 </p>
</div>
@push('head')
<style>@media print { nav, .no-print, [role=alert] { display: none !important; } body { background: #fff; } main { margin: 0 !important; } #receipt { box-shadow: none; max-width: none !important; } .status { border: 1px solid currentColor; } }</style>
@endpush
@endsection
