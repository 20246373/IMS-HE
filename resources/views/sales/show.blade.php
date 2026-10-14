@extends('layouts.app')
@section('title','Sale Details')
@section('content')
<div class="card">
 <h3>Invoice #{{ $invoice->invoice_id }}</h3>
 <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
 <p><strong>Customer:</strong> {{ $invoice->customer?->last_name.', '.$invoice->customer?->first_name ?? 'Walk-in' }}</p>
 <p><strong>Employee:</strong> {{ $invoice->employee?->last_name.', '.$invoice->employee?->first_name }}</p>
 <p><strong>Status:</strong> {{ $invoice->status }}</p>
 <p><strong>Receipt Printed:</strong> {{ $invoice->receipt_printed_at ? $invoice->receipt_printed_at->format('Y-m-d H:i:s') : 'Not printed yet' }}</p>
 <h4>Items</h4>
 <table>
  <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead>
  <tbody>
   @foreach($invoice->items as $item)
    <tr>
     <td>{{ $item->product->product_name }}</td>
     <td>{{ $item->quantity }}</td>
     <td>{{ number_format($item->price, 2) }}</td>
     <td>{{ number_format($item->subtotal, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
  <tfoot><tr><th colspan="3">Total</th><th>{{ number_format($invoice->total_amount, 2) }}</th></tr></tfoot>
 </table>
 @if($invoice->payment)
 <h4>Payment</h4>
 <p><strong>Method:</strong> {{ $invoice->payment->payment_method }}</p>
 <p><strong>Amount Paid:</strong> {{ number_format($invoice->payment->amount_paid, 2) }}</p>
 <p><strong>Change:</strong> {{ number_format($invoice->payment->change_amount, 2) }}</p>
 @endif
 <p style="margin-top:1rem">
  <a href="{{ route('sales.receipt', $invoice) }}" class="btn" target="_blank">Print as PDF</a>
  <a href="{{ route('sales.index') }}" class="btn btn-alt">Back</a>
 </p>
</div>
@endsection
