@extends('layouts.app')
@section('title','PO #'.$po->id)
@section('content')
<div class="card">
 <h3>Purchase order #{{ $po->id }} <span class="badge {{ $po->status==='Delivered' ? 'green' : 'amber' }}">{{ $po->status }}</span></h3>
 <p>Supplier: <strong>{{ $po->supplier->name }}</strong> &middot; Ordered {{ $po->order_date->format('M d, Y') }}
  @if($po->received_at) &middot; Fully received {{ $po->received_at->format('M d, Y') }}@endif</p>
 <table>
  <tr><th>Product</th><th>Ordered</th><th>Received</th><th>Unit cost</th><th>Subtotal</th></tr>
  @foreach($po->items as $it)
  <tr><td>{{ $it->product->name }}</td><td>{{ $it->quantity }}</td><td>{{ $it->quantity_received }}</td>
   <td>{{ number_format($it->unit_cost,2) }}</td><td>{{ number_format($it->subtotal,2) }}</td></tr>
  @endforeach
  <tr><th colspan="4" style="text-align:right">Total</th><th>&#8369;{{ number_format($po->total(),2) }}</th></tr>
 </table>
 <p>@if($po->status==='Pending')<a class="btn" href="{{ route('deliveries.show',$po) }}">Receive delivery</a>@endif
 <a class="btn muted" href="{{ route('purchase-orders.index') }}">Back</a></p>
</div>
@endsection
