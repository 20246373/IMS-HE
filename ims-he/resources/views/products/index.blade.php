@extends('layouts.app')
@section('title','Products')
@section('heading', 'Products')
@section('page-actions')<a class="btn" href="{{ route('products.create') }}">+ Add product</a>@endsection
@section('content')
<div class="card">
 <form method="GET" class="report-filter">
  <label>Search <input name="q" placeholder="Name or brand" value="{{ request('q') }}"></label>
  <label>Category
  <select name="category"><option value="">All categories</option>
   @foreach($categories as $c)<option value="{{ $c->category_id }}" @selected((string) request('category') === (string) $c->category_id)>{{ $c->category_name }}</option>@endforeach
  </select>
  </label>
  <button>Filter</button>
 </form>
 <table>
  <tr><th>Photo</th><th>Name</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th>Reorder</th><th>Actions</th></tr>
  @forelse($products as $p)
  <tr>
   <td>@if($p->image_path)<img src="{{ asset('storage/' . $p->image_path) }}" alt="" style="height:36px;width:36px;object-fit:cover;border-radius:4px">@endif</td>
   <td>{{ $p->product_name }}</td>
   <td>{{ $p->brand }}</td>
   <td>{{ $p->category?->category_name }}</td>
   <td>{{ money($p->unit_price) }}</td>
   <td class="{{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</td>
   <td>{{ $p->reorder_level }}</td>
   <td>
    <a class="btn" href="{{ route('products.stock-history', $p) }}">Stock history</a>
    <a class="btn" href="{{ route('products.edit', $p) }}">Edit</a>
    <form method="POST" action="{{ route('products.destroy', $p) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
   </td>
  </tr>
  @empty
  <tr><td colspan="8">No products found.</td></tr>
  @endforelse
 </table>
 {{ $products->links() }}
</div>
@endsection
