@extends('layouts.app')
@section('title','Home')
@section('content')
<div class="card">
 <h2>Ibay Enterprises Hardware</h2>
 <p>Browse tools, plumbing, electrical and building supplies.</p>
 <form method="GET" action="{{ route('catalog.index') }}" class="search">
  <input name="q" placeholder="Search products" maxlength="100">
  <button>Search</button>
 </form>
 <p class="cats">
  @foreach($categories as $c)<a href="{{ route('catalog.index', ['category' => $c->category_id]) }}">{{ $c->category_name }}</a>@endforeach
 </p>
</div>

<h3>Featured products</h3>
@if($featured->isEmpty())
 <div class="card">No products available yet.</div>
@else
 <div class="grid">@foreach($featured as $product)@include('catalog._card')@endforeach</div>
@endif
<p><a class="btn" href="{{ route('catalog.index') }}">View all products</a></p>
@endsection
