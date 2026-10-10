@extends('layouts.app')
@section('title','Deliveries')
@section('content')
<div class="card"><h3>Pending purchase orders</h3>
 <table>
  <tr><th>PO #</th><th>Date</th><th>Supplier</th><th></th></tr>
  @forelse($orders as $o)
  <tr><td>{{ $o->id }}</td><td>{{ $o->order_date->format('M d, Y') }}</td><td>{{ $o->supplier->name }}</td>
   <td><a class="btn" href="{{ route('deliveries.show',$o) }}">Receive</a></td></tr>
  @empty <tr><td colspan="4">No pending purchase orders.</td></tr> @endforelse
 </table>
</div>
@endsection
