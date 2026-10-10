@extends('layouts.app')
@section('title','Home')
@section('content')
<div class="hero">
 <h1>Smart hardware solutions for modern builds</h1>
 <p>Hand tools, power tools, plumbing, electrical, paint and fasteners from Ibay Enterprises Hardware.</p>
 <a class="btn" href="{{ route('catalog') }}">Shop all products</a>
</div>

@if($categories->count())
<div class="section-title"><h3>Shop by category</h3></div>
<div class="cattiles">
 @foreach($categories as $c)
  <a class="cattile" href="{{ route('catalog',['category'=>$c->id]) }}">
   <span class="ic">{{ mb_strtoupper(mb_substr($c->name,0,1)) }}</span>
   <span>{{ $c->name }}</span><small>{{ $c->products_count }} item(s)</small>
  </a>
 @endforeach
</div>
@endif

<div class="section-title"><h3>Featured products</h3><a href="{{ route('catalog') }}">See all &rsaquo;</a></div>
<div class="pgrid">
 @forelse($featured as $p) @include('catalog._card') @empty <div class="card">No products yet.</div> @endforelse
</div>
@endsection
