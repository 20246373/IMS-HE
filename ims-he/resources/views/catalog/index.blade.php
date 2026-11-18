@extends('layouts.app')
@section('title','Products')
@section('heading', 'Products')
@section('content')
<div class="shop">
 <aside class="shop-side">
  <div class="card">
   <h3>Filter</h3>
   <form method="GET" action="{{ route('catalog.index') }}">
    <label>Search</label>
    <input name="q" value="{{ $q }}" placeholder="Name or brand" maxlength="100">
    <label>Category</label>
    <select name="category" aria-label="Category">
     <option value="">All categories</option>
     @foreach($categories as $c)
      <option value="{{ $c->category_id }}" @selected($categoryId == $c->category_id)>{{ $c->category_name }}</option>
     @endforeach
    </select>
    <label>Brand</label>
    <select name="brand" aria-label="Brand">
     <option value="">All brands</option>
     @foreach($brands as $b)
      <option value="{{ $b }}" @selected($brand === $b)>{{ $b }}</option>
     @endforeach
    </select>
    <button class="btn">Apply Filters</button>
    @if($q !== '' || $categoryId || $brand !== '')<a href="{{ route('catalog.index') }}">Clear</a>@endif
   </form>
  </div>

  <div class="card shop-cats">
   <h4>Categories</h4>
   <a href="{{ route('catalog.index', array_filter(['q' => $q, 'brand' => $brand])) }}"
      class="{{ !$categoryId ? 'active' : '' }}">All categories <small>{{ $categories->sum('products_count') }}</small></a>
   @foreach($categories as $c)
    <a href="{{ route('catalog.index', array_filter(['q' => $q, 'brand' => $brand, 'category' => $c->category_id])) }}"
       class="{{ $categoryId == $c->category_id ? 'active' : '' }}">
     {{ $c->category_name }} <small>{{ $c->products_count }}</small>
    </a>
   @endforeach
  </div>
 </aside>

 <div class="shop-main">
  <p class="shop-count">{{ $products->total() }} product(s)</p>

  @if($products->isEmpty())
   <div class="card">No products match your search.</div>
  @else
   <div class="grid">@foreach($products as $product)@include('catalog._card')@endforeach</div>
   @if($products->hasPages())
    <p class="pager">
     @if($products->onFirstPage())<span>Previous</span>@else<a href="{{ $products->previousPageUrl() }}">Previous</a>@endif
     <span>Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
     @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
    </p>
   @endif
  @endif
 </div>
</div>
@endsection
