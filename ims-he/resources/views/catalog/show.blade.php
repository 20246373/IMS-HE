@extends('layouts.app')
@section('title',$product->name)
@section('content')
<div class="card">
 <h2>{{ $product->name }}</h2>
 <p>{{ $product->brand }} {{ $product->category ? '- '.$product->category->name : '' }}</p>
 <p>{{ $product->description }}</p>
 <p><strong>&#8369;{{ number_format($product->unit_price,2) }}</strong> &middot; {{ $product->stock_quantity > 0 ? $product->stock_quantity.' in stock' : 'Out of stock' }}</p>
 <a class="btn" href="{{ route('catalog') }}">Back</a>
</div>
@endsection
