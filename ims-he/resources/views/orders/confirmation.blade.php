@extends('layouts.app')
@section('title', 'Order Placed')
@section('heading', 'Order Confirmation')

@section('content')
<div style="max-width: 680px; margin: 0 auto;">
    
    {{-- Success Alert Banner --}}
    <x-alert type="success" title="Order Successfully Placed!">
        Thank you! Your order <strong>#{{ $invoice->invoice_id }}</strong> has been received and is being processed.
    </x-alert>

    {{-- Order Summary Card --}}
    <div class="checkout-card" style="margin-top: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.85rem; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.15rem; color: #111827;">Order Summary</h3>
            <span style="font-size: 0.85rem; color: #6b7280;">Date: {{ $invoice->invoice_date->format('M d, Y') }}</span>
        </div>

        <table class="cart-table-modern" style="margin-bottom: 1.25rem;">
            <tbody>
                <tr>
                    <td style="color: #4b5563; font-weight: 500;">Order Reference</td>
                    <td style="text-align: right; font-weight: 700; color: #111827;">#{{ $invoice->invoice_id }}</td>
                </tr>
                <tr>
                    <td style="color: #4b5563; font-weight: 500;">Status</td>
                    <td style="text-align: right;">
                        @include('orders._status', ['status' => $invoice->status])
                    </td>
                </tr>
                <tr>
                    <td style="color: #4b5563; font-weight: 500;">Total Quantity</td>
                    <td style="text-align: right; font-weight: 600; color: #111827;">{{ $invoice->items->sum('quantity') }} item(s)</td>
                </tr>
                <tr>
                    <td style="color: #4b5563; font-weight: 500;">Payment Method</td>
                    <td style="text-align: right; font-weight: 600; color: #111827;">{{ $invoice->payment?->payment_method ?? 'Cash' }}</td>
                </tr>
                <tr style="border-top: 2px dashed #e5e7eb;">
                    <td style="font-size: 1rem; font-weight: 700; color: #111827; padding-top: 0.85rem;">Grand Total</td>
                    <td style="text-align: right; font-size: 1.25rem; font-weight: 800; color: #1f3a5f; padding-top: 0.85rem;">
                        {{ money($invoice->total_amount) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Next Steps & Delivery Info Card --}}
    <div class="checkout-card">
        <h3 style="margin-top: 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; font-size: 1.1rem; color: #111827;">
            What Happens Next?
        </h3>
        <p style="font-size: 0.95rem; color: #374151; line-height: 1.6; margin-top: 0.75rem;">
            Our store team will contact you at <strong style="color: #111827;">{{ $invoice->customer->contact_number }}</strong> to verify your order and arrange fulfillment to:
        </p>
        
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.85rem 1rem; font-size: 0.9rem; color: #1f2937; margin: 0.75rem 0 1.25rem 0;">
            📍 <strong>Delivery Address:</strong> {{ $invoice->customer->address }}
        </div>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
            <a class="btn-primary" href="{{ route('customer.orders.show', $invoice) }}" style="flex: 1; text-align: center; min-width: 140px;">
                View Receipt
            </a>
            <a class="btn-primary" href="{{ route('customer.orders.index') }}" style="flex: 1; text-align: center; background: #4b5563; min-width: 140px;">
                My Orders
            </a>
            <a class="btn-primary" href="{{ route('catalog.index') }}" style="flex: 1; text-align: center; background: #ffffff; color: #1f3a5f; border: 1px solid #1f3a5f; min-width: 140px;">
                Continue Shopping
            </a>
        </div>
    </div>

</div>
@endsection