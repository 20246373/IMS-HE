@extends('layouts.app')
@section('title', 'My Cart')
@section('heading', 'My Cart')

@section('content')
<div class="grid-checkout">
    {{-- Left: Items List --}}
    <div class="checkout-card">
        @if($warnings)
            <x-alert type="warning" title="Your cart was updated">
                <ul>@foreach($warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
            </x-alert>
        @endif

        @if($items->isEmpty())
            <div style="text-align: center; padding: 2.5rem 1rem;">
                <p style="font-size: 1.1rem; color: #6b7280;">Your shopping cart is currently empty.</p>
                <a class="btn-primary" href="{{ route('catalog.index') }}" style="margin-top: 1rem;">Browse Hardware Inventory</a>
            </div>
        @else
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">
                <h3 style="margin:0; font-size: 1.1rem;">Cart Items ({{ $items->count() }})</h3>
                <a href="{{ route('catalog.index') }}" style="color: #1f3a5f; text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr; Continue Shopping</a>
            </div>

            <table class="cart-table-modern">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php($p = $item->product)
                        <tr>
                            <td>
                                <strong><a href="{{ route('catalog.show', $p) }}" style="color: #111827; text-decoration: none;">{{ $p->product_name }}</a></strong>
                                @if($p->brand)
                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.15rem;">Brand: {{ $p->brand }}</div>
                                @endif
                            </td>
                            <td style="white-space: nowrap;">{{ money($p->unit_price) }}</td>
                            <td style="text-align: center;">
                                <form method="POST" action="{{ route('customer.cart.update', $item) }}" class="qty cart-qty" data-saved="{{ $item->quantity }}" style="display: inline-block;">
                                    @csrf 
                                    @method('PATCH')
                                    @include('partials.qty', ['value' => $item->quantity, 'max' => max(1, $p->stock_quantity)])
                                </form>
                            </td>
                            <td style="text-align: right; font-weight: 600; white-space: nowrap;">
                                {{ money($item->quantity * $p->unit_price) }}
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('customer.cart.remove', $item) }}" style="display: inline;">
                                    @csrf 
                                    @method('DELETE')
                                    <button class="btn-danger-sm" type="submit" title="Remove Item">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Right: Order Summary Sidebar --}}
    @if(!$items->isEmpty())
        <div>
            <div class="checkout-summary-box">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; font-size: 1.1rem;">Order Summary</h3>
                
                <div style="display: flex; justify-content: space-between; margin: 0.75rem 0; font-size: 0.95rem;">
                    <span style="color: #4b5563;">Subtotal</span>
                    <strong style="color: #111827;">{{ money($total) }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin: 0.75rem 0; font-size: 0.95rem;">
                    <span style="color: #4b5563;">Fulfillment</span>
                    <span style="color: #059669; font-size: 0.85rem; font-weight: 600;">Calculated at Checkout</span>
                </div>

                <div style="border-top: 2px dashed #e5e7eb; padding-top: 0.75rem; margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 1rem; font-weight: 700;">Total</span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: #1f3a5f;">{{ money($total) }}</span>
                </div>

                <a href="{{ route('customer.checkout.show') }}" class="btn-primary" style="width: 100%; box-sizing: border-box; margin-top: 1.25rem;">
                    Proceed to Checkout &rarr;
                </a>
            </div>
        </div>
    @endif
</div>
@endsection