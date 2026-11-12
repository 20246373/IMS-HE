@extends('layouts.app')
@section('title','Products')
@section('content')
<div class="card">
 <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem">
  <h3 style="margin:0">Products</h3><a class="btn" href="{{ route('products.create') }}">+ Add product</a>
 </div>
 <form method="GET" style="margin:.8rem 0">
  <input name="q" placeholder="Search" value="{{ request('q') }}">
  <select name="category"><option value="">All categories</option>
   @foreach($categories as $c)<option value="{{ $c->category_id }}" @selected((string) request('category') === (string) $c->category_id)>{{ $c->category_name }}</option>@endforeach
  </select>
  <button>Filter</button>
 </form>
 <table>
  <tr><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Reorder</th><th>Status</th><th></th></tr>
  @forelse($products as $p)
  <tr style="{{ $p->is_active ? '' : 'opacity:.55' }}">
   <td>@if($p->image_path)<img src="{{ asset('storage/' . $p->image_path) }}" alt="" style="height:36px;width:36px;object-fit:cover;border-radius:4px">@endif</td>
   <td>{{ $p->product_name }}</td>
   <td>{{ $p->category?->category_name }}</td>
   <td>{{ number_format($p->unit_price, 2) }}</td>
   <td class="{{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</td>
   <td>{{ $p->reorder_level }}</td>
   <td>{{ $p->is_active ? 'Active' : 'Hidden' }}</td>
   <td>
    <a class="btn" href="{{ route('products.edit', $p) }}">Edit</a>
    <form method="POST" action="{{ route('products.toggle', $p) }}" class="inline">@csrf @method('PATCH')<button>{{ $p->is_active ? 'Hide' : 'Show' }}</button></form>
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
