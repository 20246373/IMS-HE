@extends('layouts.app')
@section('title','Edit category')
@section('content')
<div class="card" style="max-width:420px"><h3>Edit category</h3>
 <form method="POST" action="{{ route('categories.update',$category) }}">@csrf @method('PUT')
  <label>Name <input name="name" value="{{ old('name',$category->name) }}" required></label>
  <p><button>Save</button> <a class="btn muted" href="{{ route('categories.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
