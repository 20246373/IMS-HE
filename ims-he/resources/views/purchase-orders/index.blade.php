@extends('layouts.app')
@section('title','Purchase Orders')
@section('content')
<div class="card">
 <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem">
  <h2>Purchase Orders</h2>
  <a class="btn" href="{{ route('purchase-orders.create') }}">+ New purchase order</a>
 </div>
 <table>
  <tr><th>PO</th><th>Supplier</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr>
  @forelse($purchaseOrders as $purchaseOrder)
  <tr>
   <td>#{{ $purchaseOrder->po_id }}</td>
   <td>{{ $purchaseOrder->supplier->supplier_name }}</td>
   <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
   <td>{{ $purchaseOrder->status }}</td>
   <td>&#8369;{{ number_format($purchaseOrder->items->sum('subtotal'), 2) }}</td>
   <td><a class="btn" href="{{ route('purchase-orders.show', $purchaseOrder) }}">View</a></td>
  </tr>
  @empty
  <tr><td colspan="6">No purchase orders found.</td></tr>
  @endforelse
 </table>
 {{ $purchaseOrders->links() }}
</div>
@endsection
