@extends('layouts.app')
@section('title','Edit product')
@section('content')
<div class="card"><h3>Edit {{ $product->name }}</h3>
 <form method="POST" action="{{ route('products.update',$product) }}">@csrf @method('PUT')
  @include('products._fields')
  <p><button>Save</button> <a class="btn danger" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
