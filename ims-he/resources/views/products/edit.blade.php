@extends('layouts.app')
@section('title','Edit product')
@section('heading'){{ $product->product_name }}@endsection
@section('content')
<div class="card">
 <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">@csrf @method('PUT')
  <x-required-note />
  @include('products._fields')
  <p><small>Stock quantity changes only through deliveries, sales and orders.</small></p>
  <p><button>Save</button> <a class="btn" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
