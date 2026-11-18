@extends('layouts.app')
@section('title','Purchase Orders')
@push('head')
<style>
 .badge{display:inline-block;margin-left:.35rem;padding:.12rem .5rem;border-radius:999px;background:#eef0f3;color:#333;font-size:.72rem;font-weight:700;line-height:1.2;vertical-align:middle}
 .badge.amber{background:#fff5d8;color:#785b00}
</style>
@endpush
@section('heading', 'Purchase Orders')
@section('page-actions')<a class="btn" href="{{ route('purchase-orders.create') }}">+ New purchase order</a>@endsection
@section('content')
<div class="card">
 <table>
  <tr><th>PO</th><th>Supplier</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr>
  @forelse($purchaseOrders as $purchaseOrder)
  <tr>
   <td>#{{ $purchaseOrder->po_id }}</td>
   <td>{{ $purchaseOrder->supplier->supplier_name }}</td>
   <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
   <td>
    {{ $purchaseOrder->status }}
    @if($purchaseOrder->is_auto)<span class="badge amber">Auto</span>@endif
   </td>
   <td>{{ money($purchaseOrder->items->sum('subtotal')) }}</td>
   <td><a class="btn" href="{{ route('purchase-orders.show', $purchaseOrder) }}">View</a></td>
  </tr>
  @empty
  <tr><td colspan="6">No purchase orders found.</td></tr>
  @endforelse
 </table>
 {{ $purchaseOrders->links() }}
</div>
@endsection
