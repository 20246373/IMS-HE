@extends('layouts.app')
@section('title','My Orders')
@section('content')
<div class="card">
 <h2>My Orders</h2>
 @if($submittedCarts->isEmpty())
  <p>You have no orders yet.</p>
 @else
  <table>
   <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
   <tbody>
    @foreach($submittedCarts as $cart)
     <tr>
      <td>#{{ $cart->cart_id }}</td>
      <td>{{ $cart->updated_at->format('Y-m-d') }}</td>
      <td>&#8369;{{ number_format($cart->items->sum(function($item) { return $item->quantity * $item->product->unit_price; }), 2) }}</td>
      <td>{{ $cart->status }}</td>
      <td><a href="{{ route('customer.orders.show', $cart) }}" class="btn">View</a></td>
     </tr>
    @endforeach
   </tbody>
  </table>
  @if($submittedCarts->hasPages())
   <p class="pager">
    @if($submittedCarts->onFirstPage())<span>&laquo; Previous</span>@else<a href="{{ $submittedCarts->previousPageUrl() }}">&laquo; Previous</a>@endif
    <span>Page {{ $submittedCarts->currentPage() }} of {{ $submittedCarts->lastPage() }}</span>
    @if($submittedCarts->hasMorePages())<a href="{{ $submittedCarts->nextPageUrl() }}">Next &raquo;</a>@else<span>Next &raquo;</span>@endif
   </p>
  @endif
 @endif
</div>
@endsection
