@extends('layouts.app')
@section('title','My Cart')
@section('content')
<h2 style="margin-top:0">Shopping cart</h2>
@if($cart->items->isEmpty())
 <div class="card" style="text-align:center;padding:2.5rem 1rem">
  <div style="font-size:3rem">&#128722;</div>
  <p>Your cart is empty.</p>
  <a class="btn big" href="{{ route('catalog') }}">Go shopping</a>
 </div>
@else
<div class="card" style="padding-bottom:0">
 <div class="crow head"><span></span><span>Product</span><span>Unit price</span><span>Quantity</span><span>Subtotal</span><span></span></div>
 @foreach($cart->items as $item)
 @php($stock = $item->product->stock_quantity)
 <div class="crow">
  <a class="pimg" href="{{ route('catalog.show',$item->product) }}" style="text-decoration:none">{{ mb_strtoupper(mb_substr($item->product->name,0,1)) }}</a>
  <div>
   <a href="{{ route('catalog.show',$item->product) }}" style="text-decoration:none;color:var(--text)">{{ $item->product->name }}</a>
   <div style="font-size:.8rem;color:var(--muted)">{{ $item->product->brand }}</div>
   @if(! $item->product->is_active || $stock < 1)<span class="badge red">No longer available</span>
   @elseif($item->quantity > $stock)<span class="badge amber">Only {{ $stock }} left - reduce quantity</span>@endif
  </div>
  <div>&#8369;{{ number_format($item->product->unit_price,2) }}</div>
  <form method="POST" action="{{ route('cart.update',$item) }}" data-autosubmit>@csrf @method('PATCH')
   <div class="qty"><button type="button" data-d="-1">&minus;</button><input type="number" name="quantity" min="1" max="{{ max($stock,1) }}" value="{{ $item->quantity }}"><button type="button" data-d="1">+</button></div>
  </form>
  <strong style="color:var(--brand)">&#8369;{{ number_format($item->subtotal(),2) }}</strong>
  <form method="POST" action="{{ route('cart.remove',$item) }}">@csrf @method('DELETE')<button class="outline btn" style="color:var(--red);border-color:var(--red);background:#fff">Remove</button></form>
 </div>
 @endforeach
</div>
<div class="sumbar">
 <span style="color:var(--muted)">Total ({{ $cart->items->sum('quantity') }} item(s)):</span>
 <span class="tot">&#8369;{{ number_format($cart->total(),2) }}</span>
 <button class="big" disabled title="Checkout comes in the second half">Check out</button>
</div>
@endif
@endsection
