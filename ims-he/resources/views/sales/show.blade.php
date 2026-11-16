@extends('layouts.app')
@section('title','Invoice #'.$invoice->invoice_id)
@section('content')
<div class="card" id="receipt" style="max-width:640px">
 <h2>Invoice #{{ $invoice->invoice_id }}</h2>
 <p>
  {{ $invoice->invoice_date->format('M d, Y') }} &middot; {{ $invoice->channel }} &middot; {{ $invoice->status }}
  @if($invoice->employee) &middot; Served by {{ trim($invoice->employee->first_name.' '.$invoice->employee->last_name) }} @endif
  @if($invoice->customer) &middot; {{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }} @endif
 </p>
 <table>
  <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
  @foreach($invoice->items as $item)
  <tr>
   <td>{{ $item->product?->product_name ?? 'Item #' . $item->product_id }}</td>
   <td>{{ $item->quantity }}</td>
   <td>&#8369;{{ number_format($item->price, 2) }}</td>
   <td>&#8369;{{ number_format($item->subtotal, 2) }}</td>
  </tr>
  @endforeach
  <tr><th colspan="3">Total</th><th>&#8369;{{ number_format($invoice->total_amount, 2) }}</th></tr>
 </table>
 @if($invoice->payment)
 <p>
  Paid via <strong>{{ $invoice->payment->payment_method }}</strong>:
  &#8369;{{ number_format($invoice->payment->amount_paid, 2) }}
  &middot; Change: &#8369;{{ number_format($invoice->payment->change_amount, 2) }}
 </p>
 @endif
 <p>
  <button type="button" onclick="window.print()">Print receipt</button>
  <a class="btn" href="{{ route('sales.index') }}">Back to sales</a>
 </p>
</div>
@endsection
