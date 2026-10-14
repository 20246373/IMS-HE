@extends('layouts.app')
@section('title','Edit product')
@section('content')
<div class="card" style="max-width:520px"><h3>Edit {{ $product->name }}</h3>
 <form method="POST" action="{{ route('products.update',$product) }}">@csrf @method('PUT')
  @include('products._fields')
  <p><small>Changing stock here records an "Adjustment" in the inventory log.</small></p>
  <p><button>Save</button> <a class="btn muted" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
