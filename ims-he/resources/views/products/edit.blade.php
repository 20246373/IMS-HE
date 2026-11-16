@extends('layouts.app')
@section('title','Edit product')
@section('content')
<div class="card" style="max-width:520px"><h3>Edit {{ $product->product_name }}</h3>
 <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">@csrf @method('PUT')
  @include('products._fields')
  <p><small>Changing the stock quantity here is recorded in the inventory log as an Adjustment.</small></p>
  <p><button>Save</button> <a class="btn" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
