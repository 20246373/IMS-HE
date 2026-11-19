@extends('layouts.app')
@section('title','Products')
@section('heading', 'Products')

@push('head')
<style>
  /* Safe Shop View Overrides */
  .shop {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 1.5rem;
    align-items: start;
  }

  .shop-side .card {
    padding: 1.25rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(15, 31, 51, 0.04);
  }

  .shop-side h3, .shop-side h4 {
    margin: 0 0 0.75rem 0;
    color: #1f3a5f;
    font-size: 1.05rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.5rem;
  }

  .shop-side label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #475569;
    margin-top: 0.6rem;
  }

  .shop-side input, .shop-side select {
    width: 100%;
    box-sizing: border-box;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.5rem;
    font-size: 0.9rem;
  }

  .shop-main .shop-count {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.85rem;
  }

  /* Grid Card Image Fix */
  .shop-main .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 1.25rem;
  }

  .shop-main .card.product {
    margin-bottom: 0;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .shop-main .product-media {
    display: block !important;
    width: 100% !important;
    height: 160px !important;
    overflow: hidden !important;
    background: #f1f5f9;
    position: relative;
  }

  .shop-main .product-media img,
  .shop-main .product img {
    width: 100% !important;
    height: 160px !important;
    object-fit: cover !important;
    display: block !important;
  }

  .shop-main .noimg {
    width: 100% !important;
    height: 160px !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
    background: #e2e8f0;
    color: #64748b;
    font-weight: 600;
    font-size: 0.85rem;
  }

  @media (max-width: 800px) {
    .shop {
      grid-template-columns: 1fr;
    }
  }
</style>
@endpush

@section('content')
<div class="shop">
 {{-- Left Filter Sidebar --}}
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

    <button class="btn" style="width: 100%; margin-top: 0.85rem; padding: 0.6rem;">Apply Filters</button>
    @if($q !== '' || $categoryId || $brand !== '')
     <div style="text-align: center; margin-top: 0.5rem;">
      <a href="{{ route('catalog.index') }}" style="font-size: 0.85rem; color: #64748b; text-decoration: underline;">Clear Filters</a>
     </div>
    @endif
   </form>
  </div>
 </aside>

 {{-- Right Product Grid --}}
 <div class="shop-main">
  <p class="shop-count">{{ $products->total() }} product(s) available</p>

  @if($products->isEmpty())
   <div class="card" style="padding: 2.5rem 1rem; text-align: center; color: #64748b;">
    No products match your search or filter selection.
   </div>
  @else
   <div class="grid">
    @foreach($products as $product)
     @include('catalog._card')
    @endforeach
   </div>

   @if($products->hasPages())
    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 1.5rem;" class="pager">
     @if($products->onFirstPage())
      <span>Previous</span>
     @else
      <a href="{{ $products->previousPageUrl() }}">Previous</a>
     @endif
     
     <span>Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
     
     @if($products->hasMorePages())
      <a href="{{ $products->nextPageUrl() }}">Next</a>
     @else
      <span>Next</span>
     @endif
    </div>
   @endif
  @endif
 </div>
</div>
@endsection