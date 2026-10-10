@extends('layouts.app')
@section('title','Catalog')
@section('content')
@php($sortLinks = ['popular'=>'Popular','latest'=>'Latest','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low'])
<div class="shopwrap">
 <div class="filters">
  <h4>Categories</h4>
  <a class="flink {{ request('category') ? '' : 'on' }}" href="{{ route('catalog', array_filter(['q'=>request('q'),'sort'=>request('sort'),'in_stock'=>request('in_stock')])) }}">All categories</a>
  @foreach($categories as $c)
   <a class="flink {{ request('category')==$c->id ? 'on' : '' }}" href="{{ route('catalog', array_filter(['q'=>request('q'),'category'=>$c->id,'sort'=>request('sort'),'in_stock'=>request('in_stock')])) }}">{{ $c->name }}</a>
  @endforeach
  <h4 style="margin-top:1.2rem">Availability</h4>
  <a class="flink {{ request()->boolean('in_stock') ? 'on' : '' }}" href="{{ request()->boolean('in_stock') ? request()->fullUrlWithQuery(['in_stock'=>null,'page'=>null]) : request()->fullUrlWithQuery(['in_stock'=>1,'page'=>null]) }}">In stock only</a>
 </div>

 <section>
  @if(request('q'))<div class="crumbs">Results for &ldquo;<strong>{{ request('q') }}</strong>&rdquo; &middot; {{ $products->total() }} product(s) &middot; <a href="{{ route('catalog') }}">Clear</a></div>@endif
  <div class="sortbar"><span>Sort by</span>
   @foreach($sortLinks as $key => $label)
    <a class="{{ $sort === $key ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['sort'=>$key,'page'=>null]) }}">{{ $label }}</a>
   @endforeach
  </div>
  <div class="pgrid">
   @forelse($products as $p) @include('catalog._card') @empty <div class="card" style="grid-column:1/-1">No products found.</div> @endforelse
  </div>
  {{ $products->links() }}
 </section>
</div>
@endsection
