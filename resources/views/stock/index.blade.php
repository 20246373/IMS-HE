@extends('layouts.app')
@section('title','Stock monitoring')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Stock monitoring</h3>
  <div>
   <a class="btn {{ request()->boolean('low') ? 'muted' : '' }}" href="{{ route('stock.index') }}">All</a>
   <a class="btn {{ request()->boolean('low') ? '' : 'muted' }}" href="{{ route('stock.index',['low'=>1]) }}">Low stock only</a>
  </div>
 </div>
 <table>
  <tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder point</th><th>Status</th></tr>
  @forelse($products as $p)
  <tr class="{{ $p->isLowStock() ? 'lowrow' : '' }}">
   <td>{{ $p->name }}</td><td>{{ $p->category?->name }}</td>
   <td class="{{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</td><td>{{ $p->reorder_point }}</td>
   <td>@if($p->stock_quantity === 0)<span class="badge red">Out of stock</span>@elseif($p->isLowStock())<span class="badge amber">Reorder</span>@else<span class="badge green">OK</span>@endif</td>
  </tr>
  @empty <tr><td colspan="5">Nothing to show.</td></tr> @endforelse
 </table>
</div>
<div class="card"><h3>Recent stock movements</h3>
 <table>
  <tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Reference</th></tr>
  @forelse($transactions as $t)
  <tr><td>{{ $t->transaction_date->format('M d, Y') }}</td><td>{{ $t->product->name }}</td>
   <td><span class="badge {{ $t->type==='IN' ? 'green' : 'amber' }}">{{ $t->type }}</span></td><td>{{ $t->quantity }}</td>
   <td>{{ $t->reference_type }}{{ $t->reference_id ? ' #'.$t->reference_id : '' }}</td></tr>
  @empty <tr><td colspan="5">No movements yet.</td></tr> @endforelse
 </table>
</div>
@endsection
