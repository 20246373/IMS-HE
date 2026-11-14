@extends('layouts.app')
@section('title','Invoice #'.$invoice->invoice_id)
@section('content')
<div class="card">
 <h3>Invoice #{{ $invoice->invoice_id }}</h3>
 <p>{{ $invoice->invoice_date->format('M d, Y') }} &middot; {{ $invoice->channel }} &middot; {{ $invoice->status }}
  @if($invoice->customer) &middot; {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }} @endif
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
  <tr><td colspan="3"><strong>Total</strong></td><td><strong>&#8369;{{ number_format($invoice->total_amount, 2) }}</strong></td></tr>
 </table>
 @if($invoice->payment)
 <p>Paid via {{ $invoice->payment->payment_method }}: &#8369;{{ number_format($invoice->payment->amount_paid, 2) }}
  &middot; Change: &#8369;{{ number_format($invoice->payment->change_amount, 2) }}</p>
 @endif
 <p><a class="btn" href="{{ route('sales.index') }}">Back to sales</a></p>
</div>
@endsection
