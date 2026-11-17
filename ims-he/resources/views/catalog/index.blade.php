@extends('layouts.app')
@section('title','Products')
@section('content')
<div class="card">
 <h2>Products</h2>
 <form method="GET" action="{{ route('catalog.index') }}" class="search">
  <input name="q" value="{{ $q }}" placeholder="Search by name or brand" maxlength="100" aria-label="Search by name or brand">
  <select name="category" aria-label="Category">
   <option value="">All categories</option>
   @foreach($categories as $c)
    <option value="{{ $c->category_id }}" @selected($categoryId == $c->category_id)>{{ $c->category_name }}</option>
   @endforeach
  </select>
  <select name="brand" aria-label="Brand">
   <option value="">All brands</option>
   @foreach($brands as $b)
    <option value="{{ $b }}" @selected($brand === $b)>{{ $b }}</option>
   @endforeach
  </select>
  <button>Filter</button>
  @if($q !== '' || $categoryId || $brand !== '')<a href="{{ route('catalog.index') }}">Clear</a>@endif
 </form>
</div>

@if($products->isEmpty())
 <div class="card">No products match your search.</div>
@else
 <p>{{ $products->total() }} product(s)</p>
 <div class="grid">@foreach($products as $product)@include('catalog._card')@endforeach</div>
 @if($products->hasPages())
  <p class="pager">
   @if($products->onFirstPage())<span>Previous</span>@else<a href="{{ $products->previousPageUrl() }}">Previous</a>@endif
   <span>Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
   @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
  </p>
 @endif
@endif
@endsection
