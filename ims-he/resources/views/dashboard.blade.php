@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="grid">
 <div class="card"><div>Today's sales</div><h2>&#8369;{{ number_format($todaySales,2) }}</h2><small>{{ $todayCount }} invoice(s)</small></div>
 <div class="card"><div>Low-stock items</div><h2 class="{{ $lowStock->count() ? 'low' : '' }}">{{ $lowStock->count() }}</h2></div>
</div>
@if($lowStock->count())
<div class="card"><h3>Reorder alerts</h3>
 <table><tr><th>Product</th><th>Stock</th><th>Reorder point</th></tr>
 @foreach($lowStock as $p)<tr><td>{{ $p->name }}</td><td class="low">{{ $p->stock_quantity }}</td><td>{{ $p->reorder_point }}</td></tr>@endforeach
 </table></div>
@endif
@endsection
