@extends('layouts.app')
@section('title','My cart')
@section('content')
<div class="card">
 <h2>My cart</h2>
 @if($cart === null || $items->isEmpty())
  <p>Your cart is empty.</p>
  <p><a class="btn" href="{{ route('catalog.index') }}">Continue shopping</a></p>
 @else
  <table>
   <tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
   @foreach($items as $item)
   <tr>
    <td><a href="{{ route('catalog.show', $item->product) }}">{{ $item->product->product_name }}</a></td>
    <td>&#8369;{{ number_format($item->product->unit_price, 2) }}</td>
    <td>
     <form method="POST" action="{{ route('customer.cart.update', $item) }}" class="inline">@csrf @method('PATCH')
      <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock_quantity }}" step="1" style="width:70px" required>
      <button>Update</button>
     </form>
    </td>
    <td>&#8369;{{ number_format($item->quantity * $item->product->unit_price, 2) }}</td>
    <td>
     <form method="POST" action="{{ route('customer.cart.remove', $item) }}" class="inline" onsubmit="return confirm('Remove this item?')">@csrf @method('DELETE')
      <button class="danger">Remove</button>
     </form>
    </td>
   </tr>
   @endforeach
   <tr><th colspan="3" style="text-align:right">Total</th><th>&#8369;{{ number_format($total, 2) }}</th><th></th></tr>
  </table>
  <p>
   <form method="POST" action="{{ route('customer.cart.clear') }}" class="inline" onsubmit="return confirm('Clear your cart?')">@csrf @method('DELETE')
    <button class="danger">Clear cart</button>
   </form>
   <a class="btn" href="{{ route('catalog.index') }}">Continue shopping</a>
  </p>
 @endif
</div>
@endsection
