@extends('layouts.app')
@section('title', 'Purchase Order #' . $purchaseOrder->po_id)
@section('heading')Purchase Order #{{ $purchaseOrder->po_id }}@endsection
@section('content')
<div class="card">
 <dl class="details">
  <div><dt>Supplier</dt><dd><strong>{{ $purchaseOrder->supplier->supplier_name }}</strong></dd></div>
  <div><dt>Order date</dt><dd>{{ $purchaseOrder->order_date->format('M d, Y') }}</dd></div>
  <div><dt>Status</dt><dd>@include('orders._status', ['status' => $purchaseOrder->status])</dd></div>
  <div><dt>Total</dt><dd><strong>{{ money($purchaseOrder->items->sum('subtotal')) }}</strong></dd></div>
 </dl>
 @if($purchaseOrder->status === 'Pending')
  <div class="details-actions">
   <a class="btn" href="{{ route('deliveries.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}">Record delivery</a>
   <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" class="inline" onsubmit="return confirm('Cancel this purchase order?')">
    @csrf @method('PATCH')
    <button class="danger">Cancel purchase order</button>
   </form>
  </div>
 @endif
 <table>
  <tr><th>Product</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Unit cost</th><th>Subtotal</th></tr>
  @foreach($purchaseOrder->items as $item)
  <tr>
   <td>{{ $item->product->product_name }}</td>
   <td>{{ $item->quantity }}</td>
   <td>{{ $item->quantity_received }}</td>
   <td>{{ max(0, $item->quantity - $item->quantity_received) }}</td>
   <td>{{ money($item->unit_cost) }}</td>
   <td>{{ money($item->subtotal) }}</td>
  </tr>
  @endforeach
  <tr><th colspan="5" style="text-align:right">Total</th><th>{{ money($purchaseOrder->items->sum('subtotal')) }}</th></tr>
 </table>
</div>
@endsection
