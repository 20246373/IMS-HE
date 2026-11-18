@extends('layouts.app')
@section('title','My Orders')
@section('heading', 'My Orders')

@section('content')
<div class="checkout-card">
    {{-- Status Filter Bar --}}
    <form method="GET" action="{{ route('customer.orders.index') }}" class="filter-bar">
        <label for="status-select" style="font-weight: 600; font-size: 0.9rem; color: #374151;">Filter Status:</label>
        <select name="status" id="status-select" aria-label="Filter by status">
            <option value="">All Statuses</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Filter</button>
        @if($status)
            <a href="{{ route('customer.orders.index') }}" style="color: #6b7280; font-size: 0.875rem; text-decoration: underline; margin-left: 0.5rem;">Clear Filter</a>
        @endif
    </form>

    @if($orders->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem;">
            <p style="font-size: 1.05rem; color: #6b7280; margin-bottom: 1.25rem;">
                {{ $status ? "You have no {$status} orders." : 'You have not placed any orders yet.' }}
            </p>
            <a class="btn-primary" href="{{ route('catalog.index') }}">Browse Hardware Inventory</a>
        </div>
    @else
        <table class="cart-table-modern">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th style="text-align: center;">Items</th>
                    <th style="text-align: right;">Total Amount</th>
                    <th>Payment Method</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $o)
                    <tr>
                        <td><strong style="color: #1f3a5f;">#{{ $o->invoice_id }}</strong></td>
                        <td style="color: #4b5563;">{{ $o->invoice_date->format('M d, Y') }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ $o->items_count }}</td>
                        <td style="text-align: right; font-weight: 700; color: #111827; white-space: nowrap;">
                            {{ money($o->total_amount) }}
                        </td>
                        <td>{{ $o->payment?->payment_method ?? 'Cash' }}</td>
                        <td style="text-align: center;">
                            @include('orders._status', ['status' => $o->status])
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('customer.orders.show', $o) }}" class="btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem; background: #ffffff; color: #1f3a5f; border: 1px solid #1f3a5f;">
                                View Receipt
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.9rem;">
                <div>
                    @if($orders->onFirstPage())
                        <span style="color: #9ca3af; cursor: not-allowed;">&laquo; Previous</span>
                    @else
                        <a href="{{ $orders->previousPageUrl() }}" style="color: #1f3a5f; font-weight: 600; text-decoration: none;">&laquo; Previous</a>
                    @endif
                </div>

                <span style="color: #6b7280;">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>

                <div>
                    @if($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}" style="color: #1f3a5f; font-weight: 600; text-decoration: none;">Next &raquo;</a>
                    @else
                        <span style="color: #9ca3af; cursor: not-allowed;">Next &raquo;</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection