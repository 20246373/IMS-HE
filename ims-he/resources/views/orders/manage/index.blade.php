@extends('layouts.app')
@section('title','Online orders')
@section('heading', 'Online Orders')
@section('content')
<div class="card">
 <form method="GET" action="{{ route('online-orders.index') }}" class="report-filter">
  <label>Status
  <select name="status">
   @foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach
   <option value="All" @selected($status === 'All')>All statuses</option>
  </select>
  </label>
  <button>Filter</button>
 </form>

 @if($orders->isEmpty())
  <p>{{ $status === 'All' ? 'There are no online orders yet.' : "There are no {$status} online orders." }}</p>
 @else
  <table class="cart-table">
   <tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
   @foreach($orders as $o)
    <tr>
     <td>#{{ $o->invoice_id }}</td>
     <td>{{ $o->invoice_date->format('M d, Y') }}</td>
     <td>{{ trim(($o->customer?->first_name ?? '').' '.($o->customer?->last_name ?? '')) ?: ($o->customer?->email ?? 'Deleted account') }}</td>
     <td>{{ $o->items_count }}</td>
     <td>{{ money($o->total_amount) }}</td>
     <td>{{ $o->payment?->payment_method }}</td>
     <td>@include('orders._status', ['status' => $o->status])</td>
     <td><a href="{{ route('online-orders.show', $o) }}">{{ $o->status === 'Pending' ? 'Review' : 'View' }}</a></td>
    </tr>
   @endforeach
  </table>
  @if($orders->hasPages())
   <p class="pager">
    @if($orders->onFirstPage())<span>Previous</span>@else<a href="{{ $orders->previousPageUrl() }}">Previous</a>@endif
    <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
    @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
   </p>
  @endif
 @endif
</div>
@endsection
