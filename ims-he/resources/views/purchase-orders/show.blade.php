@extends('layouts.app')
@section('title', 'Purchase Order #' . $purchaseOrder->po_id)
@section('content')
<div class="card">
 <h2>Purchase Order #{{ $purchaseOrder->po_id }}</h2>
 <p>Supplier: <strong>{{ $purchaseOrder->supplier->supplier_name }}</strong></p>
 <p>Order date: {{ $purchaseOrder->order_date->format('M d, Y') }}</p>
 <p>Status: <strong>{{ $purchaseOrder->status }}</strong></p>
 @if($purchaseOrder->status === 'Pending')
  <p><a class="btn" href="{{ route('deliveries.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}">Record delivery</a></p>
 @endif
 <table>
  <tr><th>Product</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Unit cost</th><th>Subtotal</th></tr>
  @foreach($purchaseOrder->items as $item)
  <tr>
   <td>{{ $item->product->product_name }}</td>
   <td>{{ $item->quantity }}</td>
   <td>{{ $item->quantity_received }}</td>
   <td>{{ max(0, $item->quantity - $item->quantity_received) }}</td>
   <td>&#8369;{{ number_format($item->unit_cost, 2) }}</td>
   <td>&#8369;{{ number_format($item->subtotal, 2) }}</td>
  </tr>
  @endforeach
  <tr><th colspan="5" style="text-align:right">Total</th><th>&#8369;{{ number_format($purchaseOrder->items->sum('subtotal'), 2) }}</th></tr>
 </table>
 <p><a class="btn" href="{{ route('purchase-orders.index') }}">Back to purchase orders</a></p>
</div>
@endsection
