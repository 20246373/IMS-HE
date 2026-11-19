@extends('layouts.app')
@section('title','Checkout')
@section('heading', 'Checkout')

@section('content')
<div class="grid-checkout">
    {{-- Left: Customer Details & Form --}}
    <div>
        @if($warnings)
            <x-alert type="warning" title="Your cart was updated">
                <ul>@foreach($warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
            </x-alert>
        @endif

        @if ($errors->any())
            <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #991b1b;">
                <strong>Please resolve the following errors:</strong>
                <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.checkout.store') }}">
            @csrf

            {{-- How the order reaches the customer: Pickup (default) or Delivery --}}
            <div class="checkout-card">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">1. How will you get your order?</h3>
                <div class="fulfillment-options" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                    @foreach($fulfillmentOptions as $option)
                        <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1.15rem; border: 1.5px solid #d1d5db; border-radius: 8px; cursor: pointer; background: #fff;">
                            <input type="radio" name="fulfillment" value="{{ $option }}" @checked(old('fulfillment', 'Pickup') === $option)>
                            <span style="font-size: 0.95rem; font-weight: 600; color: #111827;">{{ $option === 'Pickup' ? 'Pick up at store' : 'Delivery' }}</span>
                        </label>
                    @endforeach
                </div>
                <p style="font-size: 0.85rem; color: #6b7280; margin: 0.75rem 0 0;" data-show-for="Pickup">Our store team will contact you when your order is ready to pick up.</p>
                <p style="font-size: 0.85rem; color: #6b7280; margin: 0.75rem 0 0;" data-show-for="Delivery">Our store team will contact you to arrange the delivery.</p>
                @error('fulfillment')
                    <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contact details (only the ones missing from the profile are asked for) --}}
            <div class="checkout-card">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">2. Your Details</h3>
                @php($askDetails = array_values(array_diff($missing, ['address'])))

                @if($askDetails)
                    <p><small style="color: #6b7280;">Please complete your details so our store team can contact you.</small></p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        @if(in_array('first_name', $missing))
                            <x-field label="First name" name="first_name" :value="old('first_name')" maxlength="50" autocomplete="given-name" />
                        @endif
                        @if(in_array('last_name', $missing))
                            <x-field label="Last name" name="last_name" :value="old('last_name')" maxlength="50" autocomplete="family-name" />
                        @endif
                        @if(in_array('contact_number', $missing))
                            <x-field label="Contact Number" name="contact_number" :value="old('contact_number')" maxlength="14" autocomplete="tel" />
                        @endif
                    </div>
                @endif

                @if(count($askDetails) < 3)
                    <p style="font-size: 0.95rem; color: #374151; line-height: 1.6; margin-top: 0.5rem;">
                        @unless(in_array('first_name', $missing) || in_array('last_name', $missing))<strong>Name:</strong> {{ $customer->first_name }} {{ $customer->last_name }}<br>@endunless
                        @unless(in_array('contact_number', $missing))<strong>Contact:</strong> {{ $customer->contact_number }}@endunless
                    </p>
                @endif

                {{-- Delivery address: only shown (and only sent) when Delivery is chosen --}}
                <div data-show-for="Delivery" style="margin-top: 1rem;">
                    @if(in_array('address', $missing))
                        <x-field label="Delivery Address" name="address" :value="old('address')" maxlength="100" autocomplete="street-address" />
                    @else
                        <p style="font-size: 0.95rem; color: #374151; margin: 0;"><strong>Delivery address:</strong> {{ $customer->address }}</p>
                        <p style="font-size: 0.8rem; color: #6b7280; margin: 0.25rem 0 0;">To deliver somewhere else, change it on <a href="{{ route('customer.account') }}">My Account</a> first.</p>
                    @endif
                </div>
            </div>

            {{-- Payment Options (Populated from CheckoutService::PAYMENT_METHODS) --}}
            <div class="checkout-card">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">3. Payment Method</h3>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                    @foreach($methods as $key => $label)
                        <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1.15rem; border: 1.5px solid #d1d5db; border-radius: 8px; cursor: pointer; background: #fff;">
                            <input type="radio" name="payment_method" value="{{ is_int($key) ? $label : $key }}" {{ (old('payment_method') == (is_int($key) ? $label : $key) || $loop->first) ? 'checked' : '' }}>
                            <span style="font-size: 0.95rem; font-weight: 600; color: #111827;">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method')
                    <p style="color: #dc2626; font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1.05rem; font-weight: 700; margin-top: 0.5rem;">
                Place Order Now
            </button>
        </form>
    </div>

    {{-- Right: Order Summary --}}
    <div>
        <div class="checkout-summary-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">
                <h3 style="margin:0; font-size: 1.1rem;">Order Items</h3>
                <a href="{{ route('customer.cart.index') }}" style="font-size: 0.8rem; color: #1f3a5f; font-weight: 600;">Edit Cart</a>
            </div>

            <table class="cart-table-modern" style="margin-bottom: 1rem;">
                <tbody>
                    @foreach($items as $item)
                        @php($p = $item->product)
                        <tr>
                            <td style="padding: 0.6rem 0;">
                                <strong style="font-size: 0.9rem; color: #111827;">{{ $p->product_name }}</strong>
                                <div style="font-size: 0.75rem; color: #6b7280;">Qty: {{ $item->quantity }} × {{ money($p->unit_price) }}</div>
                            </td>
                            <td style="text-align: right; font-weight: 600; font-size: 0.9rem; padding: 0.6rem 0; white-space: nowrap;">
                                {{ money($item->quantity * $p->unit_price) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="border-top: 2px dashed #e5e7eb; padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; font-size: 1rem;">Total Amount</span>
                <span style="font-size: 1.3rem; font-weight: 800; color: #1f3a5f;">{{ money($total) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
 // Pickup / Delivery: show only the parts for the chosen option. Hidden inputs are disabled so they are not sent,
 // which means the address is only required (and only saved) for Delivery.
 (function () {
  var radios = document.querySelectorAll('input[name=fulfillment]');
  function apply() {
   var chosen = (document.querySelector('input[name=fulfillment]:checked') || {}).value;
   document.querySelectorAll('[data-show-for]').forEach(function (el) {
    var show = el.dataset.showFor === chosen;
    el.hidden = !show;
    el.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = !show; });
   });
  }
  radios.forEach(function (r) { r.addEventListener('change', apply); });
  apply();
 })();
</script>
@endpush
