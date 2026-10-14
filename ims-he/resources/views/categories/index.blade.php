@extends('layouts.app')
@section('title','Categories')
@section('content')
<div class="card"><h3>Add category</h3>
 <form method="POST" action="{{ route('categories.store') }}">@csrf
  <input name="category_name" placeholder="Category name" value="{{ old('category_name') }}" maxlength="50" required> <button>Add</button>
 </form>
</div>
<div class="card"><h3>Categories</h3>
 <table>
  <tr><th>Name</th><th>Products</th><th></th></tr>
  @foreach($categories as $c)
  <tr>
   <td>{{ $c->category_name }}</td><td>{{ $c->products_count }}</td>
   <td>
    <a class="btn" href="{{ route('categories.edit', $c) }}">Edit</a>
    <form method="POST" action="{{ route('categories.destroy', $c) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
   </td>
  </tr>
  @endforeach
 </table>
</div>
@endsection
