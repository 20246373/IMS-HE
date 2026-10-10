@extends('layouts.app')
@section('title','Categories')
@section('content')
<div class="card"><h3>Add category</h3>
 <form method="POST" action="{{ route('categories.store') }}" class="row">@csrf
  <input name="name" placeholder="Category name" value="{{ old('name') }}" required> <button>Add</button>
 </form>
</div>
<div class="card"><h3>Categories</h3>
 <table>
  <tr><th>Name</th><th>Products</th><th></th></tr>
  @foreach($categories as $c)
  <tr><td>{{ $c->name }}</td><td>{{ $c->products_count }}</td>
   <td class="row"><a class="btn" href="{{ route('categories.edit',$c) }}">Edit</a>
    <form method="POST" action="{{ route('categories.destroy',$c) }}" class="inline" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="danger">Delete</button></form></td></tr>
  @endforeach
 </table>
</div>
@endsection
