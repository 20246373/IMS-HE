@extends('layouts.app')
@section('title', $product->product_name)
@section('content')
<p><a href="{{ route('catalog.index') }}">Back to products</a></p>
<div class="card detail">
 @if($product->image_path)
  <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
 @else
  <div class="noimg">No image</div>
 @endif
 <div>
  <small><a href="{{ route('catalog.index', ['category' => $product->category_id]) }}">{{ $product->category->category_name }}</a></small>
  <h2>{{ $product->product_name }}</h2>
  @if($product->brand)<p class="brand">Brand: <a href="{{ route('catalog.index', ['brand' => $product->brand]) }}">{{ $product->brand }}</a></p>@endif
  <p class="price">{{ money($product->unit_price) }}</p>
  <p>@include('catalog._stock')</p>
  @if($product->description)<p>{{ $product->description }}</p>@endif
  {{-- Guests can submit this too; auth:customer sends them to login/register (UC19 includes UC01). --}}
  @if($product->isInStock())
   <form method="POST" action="{{ route('customer.cart.add', $product) }}" class="qty">@csrf
    <label>Quantity</label> @include('partials.qty', ['value' => 1, 'max' => $product->stock_quantity])
    <button>Add to cart</button>
   </form>
  @endif
 </div>
</div>
@endsection
