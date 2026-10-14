@extends('layouts.app')
@section('title','Online Orders')
@section('content')
<div class="card">
 <h3>Pending Online Orders</h3>
 @if($orders->isEmpty())
  <p>No pending online orders.</p>
 @else
  <table>
   <thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Total</th><th>Items</th><th>Action</th></tr></thead>
   <tbody>
    @foreach($orders as $order)
    <tr>
     <td>#{{ $order->invoice_id }}</td>
     <td>{{ $order->invoice_date }}</td>
     <td>{{ $order->customer->getFullNameAttribute() }}</td>
     <td>&#8369;{{ number_format($order->total_amount, 2) }}</td>
     <td>{{ $order->items->count() }} item(s)</td>
     <td>
      <a href="{{ route('sales.show', $order) }}" class="btn">View Details</a>
      <form method="POST" action="{{ route('sales.process', $order) }}" class="inline" onsubmit="return confirm('Process this order? Stock will be deducted.')">@csrf<button class="btn" style="background:#ee4d2d">Process Order</button></form>
     </td>
    </tr>
    @endforeach
   </tbody>
  </table>
  {{ $orders->links() }}
 @endif
</div>
@endsection
