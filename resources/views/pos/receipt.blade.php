@extends('layouts.app')
@section('title','Receipt')
@section('content')
<div class="card" style="max-width:480px;margin:auto">
 <h3>Receipt - Invoice #{{ $invoice->id }}</h3>
 <small>{{ $invoice->invoice_date->format('M d, Y') }} &middot; {{ $invoice->channel }} &middot; Cashier: {{ $invoice->user?->name }}<br>
 Customer: {{ $invoice->customer ? $invoice->customer->first_name.' '.$invoice->customer->last_name : 'Walk-in' }}</small>
 <table>
  <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
  @foreach($invoice->items as $it)
   <tr><td>{{ $it->product->name }}</td><td>{{ $it->quantity }}</td><td>{{ number_format($it->price,2) }}</td><td>{{ number_format($it->subtotal,2) }}</td></tr>
  @endforeach
 </table>
 <p><strong>Total: &#8369;{{ number_format($invoice->total_amount,2) }}</strong><br>
 Paid ({{ $invoice->payment->payment_method }}): &#8369;{{ number_format($invoice->payment->amount_paid,2) }}<br>
 Change: &#8369;{{ number_format($invoice->payment->change_amount,2) }}</p>
 <div class="noprint"><button onclick="window.print()">Print</button> <a class="btn" href="{{ route('pos.index') }}">New sale</a></div>
</div>
@endsection
