@extends('layouts.app')
@section('title','My orders')
@section('content')
<div class="card">
 <h2>My orders</h2>
 @if($orders->isEmpty())
  <p>You have not placed any orders yet.</p>
  <a class="btn" href="{{ route('catalog.index') }}">Browse products</a>
 @else
  <table>
   <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
   @foreach($orders as $o)
    <tr>
     <td>#{{ $o->invoice_id }}</td>
     <td>{{ $o->invoice_date->format('M d, Y') }}</td>
     <td>{{ $o->items_count }}</td>
     <td>&#8369;{{ number_format($o->total_amount, 2) }}</td>
     <td>{{ $o->payment?->payment_method }}</td>
     <td>{{ $o->status }}</td>
     <td><a href="{{ route('customer.orders.show', $o) }}">View receipt</a></td>
    </tr>
   @endforeach
  </table>
  {{ $orders->links() }}
 @endif
</div>
@endsection
