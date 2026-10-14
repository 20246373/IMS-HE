@extends('layouts.app')
@section('title','Order #'.$cart->cart_id)
@section('content')
<div class="card">
 <p><a href="{{ route('customer.orders.index') }}">&larr; Back to orders</a></p>
 <h2>Order #{{ $cart->cart_id }}</h2>
 <p><strong>Date:</strong> {{ $cart->updated_at->format('Y-m-d H:i') }}</p>
 <p><strong>Status:</strong> {{ $cart->status }}</p>
 <table style="margin-top:1rem">
  <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr></thead>
  <tbody>
   @foreach($cart->items as $item)
    <tr>
     <td>{{ $item->product->product_name }}</td>
     <td>&#8369;{{ number_format($item->product->unit_price, 2) }}</td>
     <td>{{ $item->quantity }}</td>
     <td>&#8369;{{ number_format($item->quantity * $item->product->unit_price, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <p style="margin-top:1rem"><strong>Total: &#8369;{{ number_format($cart->items->sum(function($item) { return $item->quantity * $item->product->unit_price; }), 2) }}</strong></p>
 @if($cart->status === 'Submitted')
  <p><em>Your order has been submitted. Please proceed to the counter for payment and pickup.</em></p>
 @endif
</div>
@endsection
