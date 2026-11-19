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

            {{-- Profile & Delivery Info --}}
            <div class="checkout-card">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">1. Delivery Details</h3>
                
                @if(!empty($missing))
                    <x-required-note />
                    <p><small style="color: #6b7280;">Please complete your profile details for delivery.</small></p>
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
                        @if(in_array('address', $missing))
                            <div style="grid-column: 1 / -1;">
                                <x-field label="Delivery Address" name="address" :value="old('address')" maxlength="100" autocomplete="street-address" />
                            </div>
                        @endif
                    </div>
                @else
                    <p style="font-size: 0.95rem; color: #374151; line-height: 1.6; margin-top: 0.5rem;">
                        <strong>Recipient:</strong> {{ $customer->first_name }} {{ $customer->last_name }}<br>
                        <strong>Contact:</strong> {{ $customer->contact_number }}<br>
                        <strong>Address:</strong> {{ $customer->address }}
                    </p>
                @endif
            </div>

            {{-- Payment Options (Populated from CheckoutService::PAYMENT_METHODS) --}}
            <div class="checkout-card">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">2. Payment Method</h3>
                
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
                Place Order Now &rarr;
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