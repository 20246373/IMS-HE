@extends('layouts.app')
@section('title','Checkout')
@section('heading', 'Checkout')
@section('content')
<div class="checkout-layout">
<div class="card checkout-summary">
 @if($warnings)
  <x-alert type="warning" title="Your cart was updated">
   <ul>@foreach($warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
  </x-alert>
 @endif

 <h3>Your order</h3>
 <table class="cart-table">
  <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
  @foreach($items as $item)
   @php($p = $item->product)
   <tr>
    <td>{{ $p->product_name }}</td>
    <td>{{ money($p->unit_price) }}</td>
    <td>{{ $item->quantity }}</td>
    <td>{{ money($item->quantity * $p->unit_price) }}</td>
   </tr>
  @endforeach
  <tr class="cart-total-row"><th colspan="3">Total</th><th>{{ money($total) }}</th></tr>
 </table>
 <p><a href="{{ route('customer.cart.index') }}">Change items in my cart</a></p>
</div>

<form method="POST" action="{{ route('customer.checkout.store') }}">@csrf
 <div class="card">
  <h3>Delivery details</h3>
  @if($missing)
   {{-- Registration only asks for email and password, so the rest is collected here once and saved. --}}
   <p><small>We'll save these to your account for next time.</small></p>
   <div class="form-grid">
    @if(in_array('first_name', $missing))<x-field label="First name" name="first_name" maxlength="50" autocomplete="given-name" />@endif
    @if(in_array('last_name', $missing))<x-field label="Last name" name="last_name" maxlength="50" autocomplete="family-name" />@endif
    @if(in_array('contact_number', $missing))<x-field label="Contact number" name="contact_number" maxlength="14" inputmode="numeric" pattern="[0-9]{7,14}" title="Digits only, 7 to 14 numbers" autocomplete="tel" />@endif
    @if(in_array('address', $missing))<x-field label="Delivery address" name="address" maxlength="100" autocomplete="street-address" />@endif
   </div>
  @endif
  @if(count($missing) < 4)
   <p>
    <strong>Delivering to</strong><br>
    @if(! in_array('first_name', $missing) || ! in_array('last_name', $missing)){{ trim($customer->first_name.' '.$customer->last_name) }}<br>@endif
    @unless(in_array('contact_number', $missing)){{ $customer->contact_number }}<br>@endunless
    @unless(in_array('address', $missing)){{ $customer->address }}<br>@endunless
    <small><a href="{{ route('customer.account') }}">Change in My account</a></small>
   </p>
  @endif
 </div>

 <div class="card">
  <h3 class="required">Payment method</h3>
  @foreach($methods as $m)
   <label style="font-weight:400">
    <input type="radio" name="payment_method" value="{{ $m }}" style="display:inline;width:auto" @checked(old('payment_method', $methods[0]) === $m) required>
    {{ $m === 'Cash' ? 'Cash (pay when you pick up or receive your order)' : 'GCash (we will message you the payment details)' }}
   </label>
  @endforeach
  @error('payment_method')<div class="field-error">{{ $message }}</div>@enderror
  <p><small>Payment is arranged with the store after you place the order.</small></p>
  <p>
   <button>Place order &mdash; {{ money($total) }}</button>
   <a class="btn danger" href="{{ route('customer.cart.index') }}">Cancel</a>
  </p>
 </div>
</form>
</div>
@endsection
