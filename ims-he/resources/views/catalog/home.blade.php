@extends('layouts.app')
@section('title', 'Home')

{{-- Full-width banner, rendered by the layout above <main>. --}}
@section('hero')
<section class="hero">
 <div class="hero-inner">
  <h1>Ibay Enterprises Hardware</h1>
  <p>Tools, plumbing, electrical and building supplies.</p>
  <form class="hero-search" method="GET" action="{{ route('catalog.index') }}">
   <input name="q" placeholder="Search products" maxlength="100" aria-label="Search products">
   <button>Search</button>
  </form>
  <div class="hero-links">
   <a href="{{ route('catalog.index') }}">Browse all products</a>
   @auth('customer')
    <a href="{{ route('customer.account') }}">My account</a>
   @else
    <a href="{{ route('customer.register') }}">Create an account</a>
   @endauth
  </div>
 </div>
</section>
@endsection

@section('content')
<div class="home">

 <div class="section-head"><h2>Categories</h2><a href="{{ route('catalog.index') }}">View all products</a></div>
 <div class="cat-grid">
  @foreach($categories as $c)
   <a class="cat-tile{{ isset($categoryThumbs[$c->category_id]) ? ' has-img' : '' }}" href="{{ route('catalog.index', ['category' => $c->category_id]) }}">
    @if(isset($categoryThumbs[$c->category_id]))
     <img src="{{ asset('storage/' . $categoryThumbs[$c->category_id]) }}" alt="">
    @endif
    <span>{{ $c->category_name }}</span>
   </a>
  @endforeach
 </div>

 <div class="section-head"><h2>Featured products</h2><a href="{{ route('catalog.index') }}">See all</a></div>
 @if($featured->isEmpty())
  <div class="card">No products available yet.</div>
 @else
  <div class="grid">@foreach($featured as $product)@include('catalog._card')@endforeach</div>
 @endif

 @guest('customer')
  <div class="cta">
   <p>Create a customer account to order online.</p>
   <a class="btn" href="{{ route('customer.register') }}">Create an account</a>
  </div>
 @endguest

 <footer class="home-foot">&copy; {{ date('Y') }} Ibay Enterprises Corporation</footer>
</div>
@endsection
