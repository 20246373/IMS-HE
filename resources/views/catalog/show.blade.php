@extends('layouts.app')
@section('title', $product->product_name)
@section('content')
<p><a href="{{ route('catalog.index') }}">&larr; Back to products</a></p>
<div class="card detail">
 @if($product->image_path)
  <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
 @else
  <div class="noimg">No image</div>
 @endif
 <div>
  <small><a href="{{ route('catalog.index', ['category' => $product->category_id]) }}">{{ $product->category->category_name }}</a></small>
  <h2>{{ $product->product_name }}</h2>
  <p class="price">&#8369;{{ number_format($product->unit_price, 2) }}</p>
  <p>@include('catalog._stock')</p>
  @if($product->description)<p>{{ $product->description }}</p>@endif
  @if($product->isInStock())
   <form method="POST" action="{{ route('customer.cart.add') }}">@csrf
    <label>Quantity
     <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" step="1" required>
    </label>
    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
    <button>Add to cart</button>
   </form>
  @else
   <button disabled>Out of stock</button>
  @endif
 </div>
</div>
@endsection
