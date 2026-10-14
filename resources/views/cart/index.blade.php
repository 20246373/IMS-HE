@extends('layouts.app')
@section('title','Shopping Cart')
@section('content')
<div class="card">
 <h2>Shopping Cart</h2>
 @if($cart->items->isEmpty())
  <p>Your cart is empty.</p>
  <a href="{{ route('catalog.index') }}" class="btn">Continue Shopping</a>
 @else
  <table>
   <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
   <tbody>
    @foreach($cart->items as $item)
     <tr>
      <td>{{ $item->product->product_name }}</td>
      <td>&#8369;{{ number_format($item->product->unit_price, 2) }}</td>
      <td>
       <form method="POST" action="{{ route('customer.cart.update', $item) }}" class="inline">@csrf
        <input type="hidden" name="_method" value="PATCH">
        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock_quantity }}" style="width:60px" required>
        <button>Update</button>
       </form>
      </td>
      <td>&#8369;{{ number_format($item->quantity * $item->product->unit_price, 2) }}</td>
      <td>
       <form method="POST" action="{{ route('customer.cart.remove', $item) }}" class="inline">@csrf
        <input type="hidden" name="_method" value="DELETE">
        <button class="danger">Remove</button>
       </form>
      </td>
     </tr>
    @endforeach
   </tbody>
  </table>
  <p style="margin-top:1rem"><strong>Total: &#8369;{{ number_format($cart->items->sum(function($item) { return $item->quantity * $item->product->unit_price; }), 2) }}</strong></p>
  <form method="POST" action="{{ route('customer.cart.clear') }}" class="inline">@csrf
   <input type="hidden" name="_method" value="DELETE">
   <button class="danger">Clear Cart</button>
  </form>
  <form method="POST" action="{{ route('customer.cart.submit') }}" class="inline">@csrf
   <button class="btn">Submit Order</button>
  </form>
 @endif
</div>
@endsection
