@extends('layouts.app')
@section('title', $product->product_name)
@section('heading', $product->product_name)

@push('head')
<style>
  /* Product Details Layout Polish */
  .detail-card {
    display: grid;
    grid-template-columns: minmax(0, 320px) 1fr;
    gap: 1.5rem;
    align-items: start;
    padding: 1.5rem;
  }

  .detail-media {
    width: 100%;
    height: 300px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .detail-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .detail-media .noimg {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-weight: 600;
    background: #f1f5f9;
  }

  /* Meta & Brand Badge Styling */
  .detail-meta {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
  }

  .cat-badge {
    display: inline-block;
    padding: 0.25rem 0.65rem;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
  }

  .brand-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f3a5f;
    text-decoration: none;
  }

  .brand-tag:hover {
    background: #e2e8f0;
  }

  .detail-title {
    margin: 0.25rem 0 0.5rem 0;
    font-size: 1.65rem;
    color: #0f1f33;
    line-height: 1.3;
  }

  .detail-price-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 0.75rem 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin: 0.75rem 0 1rem 0;
  }

  .detail-price {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1f3a5f;
    margin: 0;
  }

  .detail-description {
    font-size: 0.95rem;
    color: #334155;
    line-height: 1.65;
    margin-bottom: 1.25rem;
  }

  @media (max-width: 680px) {
    .detail-card {
      grid-template-columns: 1fr;
    }
    .detail-media {
      height: 240px;
    }
  }
</style>
@endpush

@section('content')
<div class="card detail-card">
 {{-- Left Image / Media Box --}}
 <div class="detail-media">
  @if($product->image_path)
   <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
  @else
   <div class="noimg">No image</div>
  @endif
 </div>

 {{-- Right Content Details --}}
 <div>
  {{-- Category & Prominent Brand Pill --}}
  <div class="detail-meta">
   <a class="cat-badge" href="{{ route('catalog.index', ['category' => $product->category_id]) }}">
    {{ $product->category->category_name }}
   </a>

   @if($product->brand)
    <a class="brand-tag" href="{{ route('catalog.index', ['brand' => $product->brand]) }}" title="Filter by {{ $product->brand }}">
     <span style="color: #64748b; font-weight: 500;">Brand:</span>
     <strong>{{ $product->brand }}</strong>
    </a>
   @endif
  </div>

  {{-- Title --}}
  <h2 class="detail-title">{{ $product->product_name }}</h2>

  {{-- Price & Stock Badge --}}
  <div class="detail-price-row">
   <span class="detail-price">{{ money($product->unit_price) }}</span>
   <div>@include('catalog._stock')</div>
  </div>

  {{-- Description --}}
  @if($product->description)
   <p class="detail-description">{{ $product->description }}</p>
  @endif

  {{-- Add to Cart Action --}}
  @if($product->isInStock())
   <form method="POST" action="{{ route('customer.cart.add', $product) }}" class="qty" data-cart-add style="margin-top: 1rem;">
    @csrf
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
     <label style="font-weight: 600; font-size: 0.9rem; color: #334155;">Quantity:</label>
     @include('partials.qty', ['value' => 1, 'max' => $product->stock_quantity])
    </div>
    <button class="btn" style="padding: 0.7rem 1.5rem; font-weight: 700; font-size: 0.95rem;">Add to Cart</button>
    <p class="cart-add-error" hidden></p>
   </form>
  @endif
 </div>
</div>
@endsection