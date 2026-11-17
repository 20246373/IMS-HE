@extends('layouts.app')
@section('title', 'Receipt #'.$invoice->invoice_id)
@section('content')
<div class="card receipt" id="receipt">
 <h2>Ibay Enterprises Corporation</h2>
 <p>Receipt #{{ $invoice->invoice_id }}<br>Date: {{ $invoice->invoice_date->format('M d, Y') }}<br>Cashier: {{ $cashierName }}<br>Customer: {{ $invoice->customer?->display_name ?: 'Walk-in customer' }}</p>
 <table>
  <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
  <tbody>
   @foreach($invoice->items as $line)
    <tr><td>{{ $line->product->product_name }}</td><td>{{ money($line->price) }}</td><td>{{ $line->quantity }}</td><td>{{ money($line->subtotal) }}</td></tr>
   @endforeach
   <tr><th colspan="3" style="text-align:right">Total</th><th>{{ money($invoice->total_amount) }}</th></tr>
  </tbody>
 </table>
 <p>Paid: {{ money($invoice->payment?->amount_paid) }}<br>Change: {{ money($invoice->payment?->change_amount) }}<br>Method: {{ $invoice->payment?->payment_method }}</p>
 <p class="no-print"><button type="button" onclick="window.print()">Print</button> <a class="btn btn-alt" href="{{ route('sales.create') }}">New sale</a></p>
</div>
@push('head')
<style>
 @media print {
  nav,.no-print,[role=alert]{display:none!important} body{background:#fff} main{margin:0!important;padding:0!important;width:80mm!important;max-width:80mm!important}.receipt{box-shadow:none;padding:0!important;margin:0!important;width:80mm!important;max-width:80mm!important;font-size:11px}.receipt table{font-size:10px}
 }
</style>
@endpush
@endsection
