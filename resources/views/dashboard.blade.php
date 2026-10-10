@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php($u = auth()->user())
<h2 style="margin-top:0">Welcome back, {{ $u->name }}</h2>
<p style="color:var(--muted);margin-top:-.4rem">{{ now()->format('l, F j, Y') }}</p>
<div class="stats">
 @if($u->hasRole('president','treasurer','store_supervisor','employee'))
 <div class="stat"><span class="lbl">Today's sales</span><span class="val">&#8369;{{ number_format($todaySales,2) }}</span><small>{{ $todayCount }} invoice(s) today</small></div>
 @endif
 @if($u->hasRole('president','inventory_clerk','store_supervisor'))
 <div class="stat {{ $lowStock->count() ? 'alert' : '' }}"><span class="lbl">Low-stock items</span><span class="val">{{ $lowStock->count() }}</span><a href="{{ route('stock.index',['low'=>1]) }}">View stock &rsaquo;</a></div>
 <div class="stat"><span class="lbl">Pending purchase orders</span><span class="val">{{ $pendingPOs }}</span><a href="{{ route('deliveries.index') }}">Receive deliveries &rsaquo;</a></div>
 @endif
</div>
@if($u->hasRole('president','inventory_clerk','store_supervisor') && $lowStock->count())
<div class="card"><h3>Reorder alerts</h3>
 <table><tr><th>Product</th><th>Stock</th><th>Reorder point</th></tr>
 @foreach($lowStock as $p)<tr><td>{{ $p->name }}</td><td class="low">{{ $p->stock_quantity }}</td><td>{{ $p->reorder_point }}</td></tr>@endforeach
 </table></div>
@endif
@if($u->hasRole('president','inventory_clerk','store_supervisor'))
@include('dashboard._pending-purchase-orders')
@endif
@endsection
