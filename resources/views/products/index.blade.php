@extends('layouts.app')
@section('title','Products')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Products</h3><a class="btn" href="{{ route('products.create') }}">+ Add product</a>
 </div>
 <form method="GET" class="row">
  <input name="q" placeholder="Search" value="{{ request('q') }}">
  <select name="category"><option value="">All categories</option>
   @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>@endforeach
  </select>
  <button>Filter</button>
 </form>
 <table>
  <tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Reorder</th><th>Status</th><th></th></tr>
  @foreach($products as $p)
  <tr style="{{ $p->is_active ? '' : 'opacity:.55' }}">
   <td>{{ $p->name }}</td><td>{{ $p->category?->name }}</td><td>{{ number_format($p->unit_price,2) }}</td>
   <td class="{{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</td><td>{{ $p->reorder_point }}</td>
   <td><span class="badge {{ $p->is_active ? 'green' : '' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span></td>
   <td class="row"><a class="btn" href="{{ route('products.edit',$p) }}">Edit</a>
    <form method="POST" action="{{ route('products.toggle',$p) }}" class="inline">@csrf @method('PATCH')<button class="muted">{{ $p->is_active ? 'Hide' : 'Show' }}</button></form></td>
  </tr>
  @endforeach
 </table>
 {{ $products->links() }}
</div>
@endsection
