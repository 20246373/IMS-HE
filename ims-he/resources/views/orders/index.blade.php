@extends('layouts.app')
@section('title','My orders')
@section('content')
<div class="card">
 <h2>My orders</h2>
 <form method="GET" action="{{ route('customer.orders.index') }}" class="search">
  <select name="status" aria-label="Filter by status">
   <option value="">All statuses</option>
   @foreach($statuses as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach
  </select>
  <button>Filter</button>
  @if($status)<a href="{{ route('customer.orders.index') }}">Clear</a>@endif
 </form>

 @if($orders->isEmpty())
  <p>{{ $status ? "You have no {$status} orders." : 'You have not placed any orders yet.' }}</p>
  <a class="btn" href="{{ route('catalog.index') }}">Browse products</a>
 @else
  <table class="cart-table">
   <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
   @foreach($orders as $o)
    <tr>
     <td>#{{ $o->invoice_id }}</td>
     <td>{{ $o->invoice_date->format('M d, Y') }}</td>
     <td>{{ $o->items_count }}</td>
     <td>{{ money($o->total_amount) }}</td>
     <td>{{ $o->payment?->payment_method }}</td>
     <td>@include('orders._status', ['status' => $o->status])</td>
     <td><a href="{{ route('customer.orders.show', $o) }}">View receipt</a></td>
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
