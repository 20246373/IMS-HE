@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="card">
 <h2>Welcome, {{ auth()->user()->employee?->full_name ?? auth()->user()->username }}</h2>
 <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
</div>
@if(!is_null($accounts))
<div class="grid">
 <div class="card"><div>User accounts</div><h2>{{ $accounts }}</h2></div>
 <div class="card"><div>Locked accounts</div><h2 class="{{ $locked ? 'low' : '' }}">{{ $locked }}</h2></div>
</div>
@endif
<div class="card">
 <h3>Pending Purchase Orders <span>({{ $pendingPurchaseOrderCount }})</span></h3>
 @if($pendingPurchaseOrders->isEmpty())
  <p>No pending purchase orders.</p>
 @else
  <table>
   <tr><th>PO</th><th>Supplier</th><th>Date</th><th>Total</th></tr>
   @foreach($pendingPurchaseOrders as $purchaseOrder)
   <tr>
    <td><a href="{{ route('purchase-orders.show', $purchaseOrder) }}">PO #{{ $purchaseOrder->po_id }}</a></td>
    <td>{{ $purchaseOrder->supplier->supplier_name }}</td>
    <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
    <td>&#8369;{{ number_format($purchaseOrder->items->sum('subtotal'), 2) }}</td>
   </tr>
   @endforeach
  </table>
 @endif
</div>
@endsection
