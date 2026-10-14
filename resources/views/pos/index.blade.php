@extends('layouts.app')
@section('title','Point of Sale')
@section('content')
<div class="card">
 <h3>Point of Sale - Pending Customer Orders</h3>
 @if($submittedCarts->isEmpty())
  <p>No pending customer orders.</p>
 @else
  @foreach($submittedCarts as $cart)
   <div style="border:1px solid #ddd;padding:1rem;margin-bottom:1rem;border-radius:5px;background:#fff">
    <div style="display:flex;justify-content:space-between;align-items:center">
     <strong>{{ $cart->customer->first_name }} {{ $cart->customer->last_name }}</strong>
     <small>Submitted: {{ $cart->updated_at->diffForHumans() }}</small>
    </div>
    <table style="margin-top:.5rem;font-size:.9rem">
     <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
     <tbody>
      @foreach($cart->items as $item)
       <tr>
        <td>{{ $item->product->product_name }}</td>
        <td>{{ $item->quantity }}</td>
        <td>&#8369;{{ number_format($item->product->unit_price, 2) }}</td>
        <td>&#8369;{{ number_format($item->quantity * $item->product->unit_price, 2) }}</td>
       </tr>
      @endforeach
     </tbody>
    </table>
    <p style="margin:.5rem 0 0 0"><strong>Total: &#8369;{{ number_format($cart->items->sum(function($item) { return $item->quantity * $item->product->unit_price; }), 2) }}</strong></p>
    @if($errors->any())
     <div class="err">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    <form method="POST" action="{{ route('pos.process.cart', $cart->cart_id) }}" style="margin-top:.5rem">
     @csrf
     <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
      <label style="margin:0">Amount Paid <input type="number" step="0.01" name="amount_paid" value="{{ $cart->items->sum(function($item) { return $item->quantity * $item->product->unit_price; }) }}" required style="width:120px"></label>
      <label style="margin:0">Payment Method <select name="payment_method" required><option value="Cash">Cash</option><option value="Card">Card</option><option value="GCash">GCash</option><option value="PayMaya">PayMaya</option></select></label>
      <button type="submit" class="btn">Process Order</button>
     </div>
    </form>
   </div>
  @endforeach
 @endif
</div>
@endsection
