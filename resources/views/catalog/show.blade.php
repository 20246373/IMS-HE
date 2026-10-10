@extends('layouts.app')
@section('title',$product->name)
@section('content')
@php($sold = (int) ($product->invoice_items_sum_quantity ?? 0))
<div class="crumbs"><a href="{{ route('home') }}">Home</a> &rsaquo; <a href="{{ route('catalog') }}">Catalog</a>
 @if($product->category) &rsaquo; <a href="{{ route('catalog',['category'=>$product->category_id]) }}">{{ $product->category->name }}</a>@endif
 &rsaquo; {{ $product->name }}</div>

<div class="card detail">
 <div class="pimg {{ $product->stock_quantity < 1 ? 'sold' : '' }}">
  @if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">@else{{ mb_strtoupper(mb_substr($product->name,0,1)) }}@endif
 </div>
 <div>
  <h1>{{ $product->name }}</h1>
  <div class="row" style="color:var(--muted);font-size:.9rem">
   <span>{{ $sold }} sold</span>
   <span class="badge {{ $product->stock_quantity > 0 ? 'green' : 'red' }}">{{ $product->stock_quantity > 0 ? 'In stock' : 'Out of stock' }}</span>
   @if($product->stock_quantity > 0 && $product->isLowStock())<span class="badge amber">Few left</span>@endif
  </div>
  <div class="pricebar">&#8369;{{ number_format($product->unit_price,2) }}</div>
  <dl class="kv">
   <dt>Brand</dt><dd>{{ $product->brand ?: '-' }}</dd>
   <dt>Category</dt><dd>{{ $product->category?->name ?: '-' }}</dd>
   <dt>Availability</dt><dd>{{ $product->stock_quantity > 0 ? $product->stock_quantity.' piece(s) available' : 'Currently unavailable' }}</dd>
  </dl>

  {{-- Guests can click this too: the cart route sends them to /login, then back here (UC19 includes UC01). --}}
  @if(! auth()->check() || auth()->user()->isCustomer())
   @if($product->stock_quantity > 0)
   <form method="POST" action="{{ route('cart.add') }}">@csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <div class="row" style="gap:1rem">
     <span style="color:var(--muted)">Quantity</span>
     <div class="qty"><button type="button" data-d="-1">&minus;</button><input type="number" name="quantity" min="1" max="{{ $product->stock_quantity }}" value="1"><button type="button" data-d="1">+</button></div>
     <button class="big">Add to cart</button>
    </div>
   </form>
   @else
   <button class="big" disabled>Out of stock</button>
   @endif
  @endif
 </div>
</div>

@if($product->description)
<div class="card"><h3>Product description</h3><p style="white-space:pre-line;margin:0">{{ $product->description }}</p></div>
@endif

@if($related->count())
<div class="section-title"><h3>You may also like</h3></div>
<div class="pgrid">
 @foreach($related as $p) @include('catalog._card') @endforeach
</div>
@endif
@endsection
