@extends('layouts.app')
@section('title','Edit category')
@section('heading'){{ $category->category_name }}@endsection
@section('content')
<div class="card">
 <form method="POST" action="{{ route('categories.update', $category) }}" class="plain-labels">@csrf @method('PUT')
  <div class="form-grid">
   <x-field label="Name" name="category_name" :value="$category->category_name" maxlength="50" />
  </div>
  <div class="actions"><button>Save</button> <a class="btn btn-alt" href="{{ route('categories.index') }}">Cancel</a></div>
 </form>
</div>
@endsection
