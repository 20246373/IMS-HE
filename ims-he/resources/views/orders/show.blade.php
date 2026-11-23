@extends('layouts.app')
@section('title','Receipt #'.$invoice->invoice_id)
@section('heading')Receipt &mdash; Order #{{ $invoice->invoice_id }}@endsection

@section('content')
<div class="checkout-card" id="receipt" style="max-width: 680px; margin: 0 auto; background: #fff;">
    
    {{-- Header Banner --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f3a5f; padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin: 0; color: #1f3a5f; font-size: 1.35rem;">Ibay Enterprises Corporation</h2>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #6b7280;">Hardware & Building Supplies</p>
        </div>
        <div style="text-align: right;">
            <span style="font-size: 0.85rem; color: #6b7280; display: block;">Invoice Date: {{ $invoice->invoice_date->format('M d, Y') }}</span>
            <div style="margin-top: 0.35rem;">
                @include('orders._status', ['status' => $invoice->status])
            </div>
        </div>
    </div>

    {{-- Recipient & Order Details Grid --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #e5e7eb; font-size: 0.9rem;">
        <div>
            <strong style="color: #111827; display: block; margin-bottom: 0.25rem;">{{ $invoice->isDelivery() ? 'Deliver To:' : 'Customer:' }}</strong>
            <div style="color: #374151;">
                {{ trim($invoice->customer->first_name.' '.$invoice->customer->last_name) }}<br>
                {{ $invoice->customer->contact_number }}
                @if($invoice->isDelivery())<br>{{ $invoice->customer->address }}@endif
            </div>
        </div>
        <div>
            <strong style="color: #111827; display: block; margin-bottom: 0.25rem;">Order Details:</strong>
            <div style="color: #374151;">
                <strong>Order #:</strong> {{ $invoice->invoice_id }}<br>
                <strong>Channel:</strong> {{ $invoice->channel ?? 'Online' }}<br>
                <strong>Pickup or Delivery:</strong> {{ $invoice->fulfillmentLabel() }}<br>
                <strong>Payment Method:</strong> {{ $invoice->payment?->payment_method ?? 'Cash' }}@if($invoice->payment?->reference_no)<br>
                <strong>GCash Ref. No.:</strong> {{ $invoice->payment->reference_no }}@endif
            </div>
        </div>
    </div>

    {{-- Product Items Breakdown --}}
    <table class="cart-table-modern" style="margin-bottom: 1.5rem;">
        <thead>
            <tr>
                <th>Product Description</th>
                <th>Unit Price</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $line)
                <tr>
                    <td>
                        <strong style="color: #111827;">{{ $line->product->product_name }}</strong>
                        @if($line->product->brand)
                            <div style="font-size: 0.75rem; color: #6b7280;">{{ $line->product->brand }}</div>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">{{ money($line->price) }}</td>
                    <td style="text-align: center; font-weight: 600;">{{ $line->quantity }}</td>
                    <td style="text-align: right; font-weight: 700; color: #111827; white-space: nowrap;">
                        {{ money($line->subtotal) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #1f3a5f;">
                <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1rem; padding-top: 0.85rem;">Grand Total</td>
                <td style="text-align: right; font-size: 1.25rem; font-weight: 800; color: #1f3a5f; padding-top: 0.85rem; white-space: nowrap;">
                    {{ money($invoice->total_amount) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Order Notes --}}
    <div style="font-size: 0.875rem; color: #4b5563; padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
        @if($invoice->status === 'Cancelled')
            <p style="color: #dc2626; font-weight: 700; margin: 0;">⚠️ This order was cancelled. No payment is due.</p>
        @else
            <p style="margin: 0;">Thank you for ordering with Ibay Enterprises! The store team will contact you regarding delivery or store pickup updates.</p>
        @endif
    </div>

    {{-- Print & Navigation Controls --}}
    <div class="no-print" style="display: flex; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        <button type="button" class="btn-primary" onclick="window.print()" style="flex: 1; background: #059669; border: none;">
            🖨️ Print Receipt
        </button>
        <a class="btn-primary" href="{{ route('customer.orders.index') }}" style="flex: 1; text-align: center; background: #4b5563;">
            My Orders
        </a>
        <a class="btn-primary" href="{{ route('catalog.index') }}" style="flex: 1; text-align: center; background: #ffffff; color: #1f3a5f; border: 1px solid #1f3a5f;">
            Continue Shopping
        </a>
    </div>
</div>

@push('head')
<style>
@media print { 
    nav, .no-print, [role=alert], header, footer { display: none !important; } 
    body { background: #fff !important; color: #000 !important; } 
    main { margin: 0 !important; padding: 0 !important; } 
    #receipt { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; } 
    .status { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; } 
}
</style>
@endpush
@endsection