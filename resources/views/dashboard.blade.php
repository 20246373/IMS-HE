@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="card">
 <h2>Welcome, {{ auth()->user()->username }}</h2>
 <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
</div>
@if(isset($accounts))
<div class="grid">
 <div class="card"><div>User accounts</div><h2>{{ $accounts }}</h2></div>
 <div class="card"><div>Locked accounts</div><h2 class="{{ $locked ? 'low' : '' }}">{{ $locked }}</h2></div>
</div>
@endif
@if(isset($totalIncome))
<div class="grid">
 <div class="card"><div>Total Income</div><h2>{{ number_format($totalIncome, 2) }}</h2></div>
 <div class="card"><div>Total Expenses</div><h2>{{ number_format($totalExpense, 2) }}</h2></div>
 <div class="card"><div>Net</div><h2>{{ number_format($totalIncome - $totalExpense, 2) }}</h2></div>
</div>
@endif
@if(isset($lowStockCount))
<div class="grid">
 <div class="card"><div>Low Stock Items</div><h2 class="{{ $lowStockCount > 0 ? 'low' : '' }}">{{ $lowStockCount }}</h2></div>
</div>
@endif
@if(isset($pendingPurchaseOrders))
<div class="card">
 <h3>Pending Purchase Orders <span>({{ $pendingPurchaseOrderCount ?? $pendingPurchaseOrders->count() }})</span></h3>
 @if($pendingPurchaseOrders->isEmpty())
  <p>No pending purchase orders.</p>
 @else
  <table>
   <thead><tr><th>PO</th><th>Supplier</th><th>Date</th><th>Total</th></tr></thead>
   <tbody>
   @foreach($pendingPurchaseOrders as $purchaseOrder)
   <tr>
    <td><a href="{{ route('purchase-orders.show', $purchaseOrder) }}">PO #{{ $purchaseOrder->po_id }}</a></td>
    <td>{{ $purchaseOrder->supplier->supplier_name }}</td>
    <td>{{ $purchaseOrder->order_date }}</td>
    <td>{{ number_format($purchaseOrder->items->sum('subtotal'), 2) }}</td>
   </tr>
   @endforeach
   </tbody>
  </table>
 @endif
</div>
@endif
@if(isset($todaySales))
<div class="grid">
 <div class="card"><div>Today's Sales</div><h2>{{ $todaySales }}</h2></div>
 <div class="card"><div>Today's Revenue</div><h2>{{ number_format($todaySalesAmount, 2) }}</h2></div>
</div>
@endif
@endsection

