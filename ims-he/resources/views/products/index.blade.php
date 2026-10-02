@extends('layouts.app')
@section('title','Inventory')
@section('content')
<div class="card"><h3>Add product</h3>
 <form method="POST" action="{{ route('products.store') }}">@csrf
  @include('products._fields', ['product' => null])
  <p><button>Add product</button></p>
 </form>
</div>
<div class="card">
 <form method="GET"><input name="q" placeholder="Search" value="{{ request('q') }}"> <button>Search</button></form>
 <table>
  <tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Reorder</th><th></th></tr>
  @foreach($products as $p)
  <tr>
   <td>{{ $p->name }}</td><td>{{ $p->category?->name }}</td><td>{{ number_format($p->unit_price,2) }}</td>
   <td class="{{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</td><td>{{ $p->reorder_point }}</td>
   <td><a class="btn" href="{{ route('products.edit',$p) }}">Edit</a>
    <form method="POST" action="{{ route('products.destroy',$p) }}" style="display:inline" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="danger">Delete</button></form></td>
  </tr>
  @endforeach
 </table>
 {{ $products->links() }}
</div>
@endsection
