@extends('layouts.app')
@section('title','Purchase orders')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between"><h3 style="margin:0">Purchase orders</h3><a class="btn" href="{{ route('purchase-orders.create') }}">+ New PO</a></div>
 <table>
  <tr><th>PO #</th><th>Date</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr>
  @forelse($orders as $o)
  <tr><td>{{ $o->id }}</td><td>{{ $o->order_date->format('M d, Y') }}</td><td>{{ $o->supplier->name }}</td>
   <td>&#8369;{{ number_format($o->total(),2) }}</td>
   <td><span class="badge {{ $o->status==='Delivered' ? 'green' : 'amber' }}">{{ $o->status }}</span></td>
   <td><a class="btn" href="{{ route('purchase-orders.show',$o) }}">View</a></td></tr>
  @empty <tr><td colspan="6">No purchase orders yet.</td></tr> @endforelse
 </table>
 {{ $orders->links() }}
</div>
@endsection
