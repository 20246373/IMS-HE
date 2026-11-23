@extends('layouts.app')
@section('title','Online order #'.$invoice->invoice_id)
@section('heading')Online Order #{{ $invoice->invoice_id }} @include('orders._status', ['status' => $invoice->status])@endsection
@section('content')
<div class="card">
 <div class="details-layout">
  <dl class="details">
   <div><dt>Order number</dt><dd>#{{ $invoice->invoice_id }}</dd></div>
   <div><dt>Date</dt><dd>{{ $invoice->invoice_date->format('M d, Y') }}</dd></div>
   <div><dt>Number of items</dt><dd>{{ $invoice->items->sum('quantity') }}</dd></div>
   <div><dt>Pickup or delivery</dt><dd><strong>{{ $invoice->fulfillmentLabel() }}</strong></dd></div>
   <div><dt>Payment method</dt><dd>{{ $invoice->payment?->payment_method }}</dd></div>
   @if($invoice->payment?->reference_no)<div><dt>GCash reference no.</dt><dd>{{ $invoice->payment->reference_no }}</dd></div>@endif
   <div><dt>Total</dt><dd><strong>{{ money($invoice->total_amount) }}</strong></dd></div>
  </dl>
  <section class="details-aside">
   <h3>Customer</h3>
   @if($invoice->customer)
    <p><strong>{{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) ?: $invoice->customer->email }}</strong><br>
    {{ $invoice->customer->email }}<br>
    {{ $invoice->customer->contact_number }}
    @if($invoice->isDelivery())<br>Deliver to: {{ $invoice->customer->address }}@endif</p>
   @else
    <p>Deleted account</p>
   @endif
  </section>
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
  <form method="POST" action="{{ route('online-orders.complete', $invoice) }}" id="complete-order">@csrf @method('PATCH')
   @if($invoice->payment?->payment_method === 'GCash')
    {{-- Paid by GCash: staff enter the Ref. No. from the customer's GCash receipt when completing the order --}}
    <div class="gcash-ref">
     <x-field label="GCash reference no." name="reference_no" inputmode="numeric" maxlength="16" autocomplete="off" placeholder="13-digit Ref. No. on the GCash receipt" />
    </div>
   @endif
  </form>
  <div class="details-actions">
   <button form="complete-order">Mark as Completed</button>
   <form method="POST" action="{{ route('online-orders.cancel', $invoice) }}" class="inline"
         onsubmit="return confirm('Cancel order #{{ $invoice->invoice_id }}? The items go back into stock. This cannot be undone.')">@csrf @method('PATCH')
    <button class="danger">Cancel order</button>
   </form>
  </div>
 @else
  <p><small>This order is {{ $invoice->status }} and can no longer be changed.</small></p>
 @endif
</div>
@endsection

@push('head')
<style>
 .gcash-ref{max-width:320px;margin-top:1rem}
</style>
@endpush
