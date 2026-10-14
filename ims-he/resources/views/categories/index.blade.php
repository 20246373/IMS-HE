@extends('layouts.app')
@section('title','Categories')
@section('content')
<div class="card"><h3>Add category</h3>
 <form method="POST" action="{{ route('categories.store') }}" class="row">@csrf
  <input name="category_name" placeholder="Category name" value="{{ old('category_name') }}" required> <button>Add</button>
 </form>
</div>
<div class="card"><h3>Categories</h3>
 <table>
  <tr><th>Name</th><th>Products</th><th>Status</th><th></th></tr>
  @foreach($categories as $c)
  <tr style="{{ $c->is_active ? '' : 'opacity:.55' }}"><td>{{ $c->category_name }}</td><td>{{ $c->products_count }}</td>
   <td>{{ $c->is_active ? 'Active' : 'Hidden' }}</td>
   <td class="row"><a class="btn" href="{{ route('categories.edit',$c) }}">Edit</a>
    <form method="POST" action="{{ route('categories.toggle',$c) }}" class="inline">@csrf @method('PATCH')
     <button class="muted">{{ $c->is_active ? 'Hide' : 'Restore' }}</button></form></td></tr>
  @endforeach
 </table>
</div>
@endsection
