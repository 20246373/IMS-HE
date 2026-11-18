@extends('layouts.app')
@section('title','Online order #'.$invoice->invoice_id)
@section('heading')Online Order #{{ $invoice->invoice_id }} @include('orders._status', ['status' => $invoice->status])@endsection
@section('content')
<div class="card" style="max-width:760px">
 <div class="form-grid">
  <p>
   <strong>Customer</strong><br>
   @if($invoice->customer)
    {{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }}<br>
    {{ $invoice->customer->email }}<br>
    {{ $invoice->customer->contact_number }}<br>
    {{ $invoice->customer->address }}
   @else
    Deleted account
   @endif
  </p>
  <p>
   <strong>Order</strong><br>
   Date: {{ $invoice->invoice_date->format('M d, Y') }}<br>
   Payment method: {{ $invoice->payment?->payment_method }}<br>
   Total: <strong>{{ money($invoice->total_amount) }}</strong>
  </p>
 </div>

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

 @if($invoice->status === 'Pending')
  <p>
   <form method="POST" action="{{ route('online-orders.complete', $invoice) }}" class="inline">@csrf @method('PATCH')
    <button>Mark as Completed</button>
   </form>
   <form method="POST" action="{{ route('online-orders.cancel', $invoice) }}" class="inline"
         onsubmit="return confirm('Cancel order #{{ $invoice->invoice_id }}? The items go back into stock. This cannot be undone.')">@csrf @method('PATCH')
    <button class="danger">Cancel order</button>
   </form>
  </p>
 @else
  <p><small>This order is {{ $invoice->status }} and can no longer be changed.</small></p>
 @endif
</div>
@endsection
