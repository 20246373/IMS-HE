@extends('layouts.app')
@section('title','Stock Management')
@section('content')
<div class="card">
 <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
  <h3 style="margin:0">Stock Management</h3>
  <div>
   <a class="btn {{ request()->boolean('low') ? 'btn-alt' : '' }}" href="{{ route('stock.index') }}">All</a>
   <a class="btn {{ request()->boolean('low') ? '' : 'btn-alt' }}" href="{{ route('stock.index',['low'=>1]) }}">Low stock only</a>
  </div>
 </div>
 <table>
  <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder Level</th><th>Status</th></tr></thead>
  <tbody>
   @forelse($products as $p)
   <tr @if($p->isLowStock()) class="low"@endif>
    <td>{{ $p->product_name }}</td>
    <td>{{ $p->category?->category_name }}</td>
    <td @if($p->isLowStock()) class="low"@endif>{{ $p->stock_quantity }}</td>
    <td>{{ $p->reorder_level }}</td>
    <td>
     @if($p->stock_quantity === 0)<span class="low">Out of stock</span>
     @elseif($p->isLowStock())<span class="low">Reorder</span>
     @else<span>OK</span>@endif
    </td>
   </tr>
   @empty <tr><td colspan="5">Nothing to show.</td></tr> @endforelse
  </tbody>
 </table>
</div>
<div class="card">
 <h3>Adjust Stock</h3>
 <form method="POST" action="{{ route('stock.adjust') }}">@csrf
  <label>Product <select name="product_id" required><option value="">Select</option>@foreach($products as $p)<option value="{{ $p->product_id }}">{{ $p->product_name }}</option>@endforeach</select></label>
  <label>Quantity <input type="number" name="quantity" required></label>
  <label>Type <select name="type" required><option value="add">Add</option><option value="deduct">Deduct</option></select></label>
  <label>Reason <input type="text" name="reason" required></label>
  <button>Adjust</button>
 </form>
</div>
<div class="card">
 <h3>Recent Adjustments</h3>
 <table>
  <thead><tr><th>Date</th><th>Product</th><th>Change</th><th>Before</th><th>After</th><th>Reason</th><th>By</th></tr></thead>
  <tbody>
   @forelse($adjustments as $a)
   <tr>
    <td>{{ $a->adjusted_at->format('M d, Y H:i') }}</td>
    <td>{{ $a->product->product_name }}</td>
    <td>{{ $a->quantity_change > 0 ? '+' : '' }}{{ $a->quantity_change }}</td>
    <td>{{ $a->quantity_before }}</td>
    <td>{{ $a->quantity_after }}</td>
    <td>{{ $a->reason }}</td>
    <td>{{ $a->adjustedBy->username }}</td>
   </tr>
   @empty <tr><td colspan="7">No adjustments yet.</td></tr> @endforelse
  </tbody>
 </table>
</div>
@endsection

