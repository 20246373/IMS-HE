@extends('layouts.app')
@section('title','Checkout')
@section('content')
<div class="card">
 <h2>Checkout</h2>

 <h3>Your order</h3>
 <table>
  <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
  @foreach($items as $item)
   @php($p = $item->product)
   <tr>
    <td>
     {{ $p->product_name }}
     @if($item->quantity > $p->stock_quantity)<div class="low">Only {{ $p->stock_quantity }} in stock</div>@endif
    </td>
    <td>&#8369;{{ number_format($p->unit_price, 2) }}</td>
    <td>{{ $item->quantity }}</td>
    <td>&#8369;{{ number_format($item->quantity * $p->unit_price, 2) }}</td>
   </tr>
  @endforeach
  <tr><th colspan="3">Total</th><th>&#8369;{{ number_format($total, 2) }}</th></tr>
 </table>
 <p><a href="{{ route('customer.cart.index') }}">&larr; Change items in my cart</a></p>
</div>

<form method="POST" action="{{ route('customer.checkout.store') }}">@csrf
 <div class="card" style="max-width:560px">
  <h3>Delivery details</h3>
  <p><small>Saved to your profile for next time.</small></p>
  <label>First name <input name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="50" required></label>
  <label>Last name <input name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="50" required></label>
  <label>Contact number <input name="contact_number" value="{{ old('contact_number', $customer->contact_number) }}" maxlength="14" inputmode="numeric" pattern="[0-9]{7,14}" title="Digits only, 7 to 14 numbers" required></label>
  <label>Address <input name="address" value="{{ old('address', $customer->address) }}" maxlength="100" required></label>
 </div>

 <div class="card" style="max-width:560px">
  <h3>Payment method</h3>
  @foreach($methods as $m)
   <label style="font-weight:400">
    <input type="radio" name="payment_method" value="{{ $m }}" style="display:inline;width:auto" @checked(old('payment_method', $methods[0]) === $m) required>
    {{ $m === 'Cash' ? 'Cash (pay when you pick up or receive your order)' : 'GCash (we will message you the payment details)' }}
   </label>
  @endforeach
  <p><small>Payment is arranged with the store after you place the order.</small></p>
  <p>
   <button>Place order &mdash; &#8369;{{ number_format($total, 2) }}</button>
   <a class="btn danger" href="{{ route('customer.cart.index') }}">Cancel</a>
  </p>
 </div>
</form>
@endsection
