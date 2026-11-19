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
 .hero p{font-size:1.1rem;margin:0;color:#dbe4ef}
 /* measuring-tape strip under the banner */
 .tape{height:28px;background-color:#f2b705;background-repeat:repeat-x;background-position:0 0,0 0;background-size:12px 10px,60px 18px;
  background-image:repeating-linear-gradient(90deg,#1a1a1a 0 2px,transparent 2px 12px),repeating-linear-gradient(90deg,#1a1a1a 0 2px,transparent 2px 60px)}
 .home{padding-top:.5rem}
 .section-head{display:flex;justify-content:space-between;align-items:baseline;margin:2rem 0 .8rem}
 .section-head h2{margin:0;font-size:1.4rem}
 .section-head a{color:#1f3a5f;text-decoration:none}
 .cat-scroller{position:relative}
 .cat-scroll{position:absolute;top:50%;transform:translateY(-50%);z-index:2;width:34px;height:34px;border-radius:50%;background:#1f3a5f;color:#fff;border:0;font-size:1.15rem;line-height:1;cursor:pointer;box-shadow:0 2px 6px #0003;display:grid;place-items:center}
 .cat-scroll:hover{background:#f2b705;color:#1a1a1a}
 .cat-scroll-prev{left:.4rem} .cat-scroll-next{right:.4rem}
 .cat-grid{display:flex;gap:1rem;overflow:hidden;padding-bottom:.6rem;scroll-snap-type:x proximity}
 .cat-grid::-webkit-scrollbar{display:none}
 .cat-tile{position:relative;display:block;flex:0 0 220px;height:120px;border-radius:6px;overflow:hidden;background:#1f3a5f;text-decoration:none;color:#fff;border-left:6px solid #f2b705;scroll-snap-align:start;transition:transform .18s ease,filter .18s ease}
 .cat-tile:hover{transform:translateY(-3px);filter:brightness(1.12)}
 .cat-tile img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
 .cat-tile.has-img::after{content:"";position:absolute;inset:0;background:linear-gradient(transparent 35%,rgba(0,0,0,.7))}
 .cat-tile span{position:absolute;left:.9rem;bottom:.7rem;z-index:1;font-weight:700;font-size:1.05rem}
 .home .grid{grid-template-columns:repeat(auto-fill,minmax(230px,1fr))}
 .home .product{padding:0 0 .9rem;border-radius:6px;overflow:hidden}
 .home .product img,.home .product .noimg{height:180px;border-radius:0}
 .home .product > :not(.product-media){margin-left:.9rem;margin-right:.9rem}
 .cta{margin:2rem 0 1rem;background:#fff;border:1px solid #dde2e8;border-left:6px solid #f2b705;border-radius:6px;padding:1.2rem 1.4rem;display:flex;gap:1rem;justify-content:space-between;align-items:center;flex-wrap:wrap}
 .cta p{margin:0}
 @media (max-width:700px){.hero::before,.hero::after{display:none}.hero-inner{padding:2.5rem 1.2rem}}
 .cat-tile {
    position: relative;
    display: block;
    flex: 0 0 220px;
    height: 120px;
    border-radius: 6px;
    overflow: hidden;
    background: linear-gradient(135deg, #1f3a5f 0%, #172d49 100%);
    text-decoration: none;
    color: #fff;
    border-left: 6px solid #f2b705;
    scroll-snap-align: start;
    transition: transform .18s ease, filter .18s ease;
}
.cat-tile img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
/* White label on the photo (".storefront" in front so the storefront link color does not win). */
.storefront .cat-tile { color: #fff; }
.storefront .cat-tile span { text-shadow: 0 1px 3px rgba(0,0,0,.6); }
.cat-tile.has-img::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(transparent 30%, rgba(0,0,0,.75));
}
</style>
@endpush

{{-- Full-width banner, rendered by the layout above <main>. --}}
@section('hero')
<section class="hero">
 <div class="hero-inner">
  <h1>Ibay Enterprises Hardware</h1>
  <p>Tools, plumbing, electrical and building supplies.</p>
 </div>
</section>
<div class="tape"></div>
@endsection

@section('content')
<div class="home">

 <div class="section-head"><h2>Featured Products</h2><a href="{{ route('catalog.index') }}">See all</a></div>
 @if($featured->isEmpty())
  <div class="card">No products available yet.</div>
 @else
  <div class="grid">@foreach($featured as $product)@include('catalog._card')@endforeach</div>
 @endif

 <div class="section-head"><h2>Shop by Category</h2><a href="{{ route('catalog.index') }}">View all products</a></div>
 <div class="cat-scroller">
  <button type="button" class="cat-scroll cat-scroll-prev" data-dir="-1" aria-label="Scroll categories left">&#8249;</button>
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
  <button type="button" class="cat-scroll cat-scroll-next" data-dir="1" aria-label="Scroll categories right">&#8250;</button>
 </div>

</div>
@endsection

@push('scripts')
<script>
 // Category row arrows: scroll about one viewport width, smoothly.
 document.querySelectorAll('.cat-scroll').forEach(function (btn) {
  btn.addEventListener('click', function () {
   var grid = btn.closest('.cat-scroller').querySelector('.cat-grid');
   grid.scrollBy({ left: Number(btn.dataset.dir) * grid.clientWidth * 0.8, behavior: 'smooth' });
  });
 });
</script>
@endpush