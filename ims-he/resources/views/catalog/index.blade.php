@extends('layouts.app')
@section('title','Store')
@section('content')
<div class="card">
 <form method="GET">
  <input name="q" placeholder="Search name or brand" value="{{ request('q') }}">
  <select name="category"><option value="">All categories</option>
   @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>@endforeach
  </select>
  <button>Search</button>
 </form>
</div>
<div class="grid">
@forelse($products as $p)
 <div class="card">
  <strong><a href="{{ route('catalog.show',$p) }}">{{ $p->name }}</a></strong>
  <div>{{ $p->brand }} {{ $p->category ? '- '.$p->category->name : '' }}</div>
  <div>&#8369;{{ number_format($p->unit_price,2) }}</div>
  <div class="{{ $p->stock_quantity > 0 ? '' : 'low' }}">{{ $p->stock_quantity > 0 ? 'In stock ('.$p->stock_quantity.')' : 'Out of stock' }}</div>
 </div>
@empty
 <div class="card">No products found.</div>
@endforelse
</div>
{{ $products->links() }}
@endsection
