@extends('layouts.app')
@section('title', 'Home')

@push('head')
<style>
 /* Homepage only. The banner is pure CSS (no image file needed). */
 main{max-width:1180px}
 .hero{position:relative;overflow:hidden;color:#fff;background-color:#17304f;
  background-image:linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px);
  background-size:32px 32px}
 .hero::before{content:"";position:absolute;right:-6%;top:-30%;width:40%;height:170%;background:#f2b705;transform:skewX(-14deg)}
 .hero::after{content:"";position:absolute;right:30%;top:-30%;width:6%;height:170%;background:#0f2036;transform:skewX(-14deg);opacity:.55}
 .hero-inner{position:relative;z-index:1;max-width:1180px;margin:0 auto;padding:4rem 1.2rem 3.6rem}
 .hero h1{font-size:clamp(2.1rem,5vw,3.4rem);line-height:1.1;margin:0 0 .6rem;max-width:14ch;letter-spacing:-.01em}
 .hero p{font-size:1.1rem;margin:0 0 1.5rem;color:#dbe4ef}
 .hero-search{display:flex;gap:.5rem;max-width:520px}
 .hero-search input{flex:1;border:0;border-radius:4px;font-size:1rem;padding:.75rem .9rem;margin:0;min-width:0}
 .hero-search button{border-radius:4px;padding:.75rem 1.5rem;background:#f2b705;color:#1a1a1a;font-weight:700}
 .hero-links{margin-top:1rem;display:flex;gap:1.2rem;flex-wrap:wrap}
 .hero-links a{color:#fff;text-decoration:underline}
 /* measuring-tape strip under the banner */
 .tape{height:28px;background-color:#f2b705;background-repeat:repeat-x;background-position:0 0,0 0;background-size:12px 10px,60px 18px;
  background-image:repeating-linear-gradient(90deg,#1a1a1a 0 2px,transparent 2px 12px),repeating-linear-gradient(90deg,#1a1a1a 0 2px,transparent 2px 60px)}
 .home{padding-top:.5rem}
 .section-head{display:flex;justify-content:space-between;align-items:baseline;margin:2rem 0 .8rem}
 .section-head h2{margin:0;font-size:1.4rem}
 .section-head a{color:#1f3a5f;text-decoration:none}
 .cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem}
 .cat-tile{position:relative;display:block;height:120px;border-radius:6px;overflow:hidden;background:#1f3a5f;text-decoration:none;color:#fff;border-left:6px solid #f2b705}
 .cat-tile img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
 .cat-tile.has-img::after{content:"";position:absolute;inset:0;background:linear-gradient(transparent 35%,rgba(0,0,0,.7))}
 .cat-tile span{position:absolute;left:.9rem;bottom:.7rem;z-index:1;font-weight:700;font-size:1.05rem}
 .cat-tile:hover{filter:brightness(1.1)}
 .home .grid{grid-template-columns:repeat(auto-fill,minmax(230px,1fr))}
 .home .product{padding:0 0 .9rem;border-radius:6px;overflow:hidden}
 .home .product img,.home .product .noimg{height:180px;border-radius:0}
 .home .product > :not(img):not(.noimg){margin-left:.9rem;margin-right:.9rem}
 .cta{margin:2rem 0 1rem;background:#fff;border:1px solid #dde2e8;border-left:6px solid #f2b705;border-radius:6px;padding:1.2rem 1.4rem;display:flex;gap:1rem;justify-content:space-between;align-items:center;flex-wrap:wrap}
 .cta p{margin:0}
 .home-foot{text-align:center;color:#888;font-size:.85rem;padding:1.5rem 0 2rem}
 @media (max-width:700px){.hero::before,.hero::after{display:none}.hero-inner{padding:2.5rem 1.2rem}}
</style>
@endpush

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
<div class="tape"></div>
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