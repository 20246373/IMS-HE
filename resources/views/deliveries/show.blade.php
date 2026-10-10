@extends('layouts.app')
@section('title','Receive PO #'.$po->id)
@section('content')
<form method="POST" action="{{ route('deliveries.store',$po) }}">@csrf
<div class="card"><h3>Receive delivery - PO #{{ $po->id }} ({{ $po->supplier->name }})</h3>
 <table>
  <tr><th>Product</th><th>Ordered</th><th>Already received</th><th>Outstanding</th><th>Receive now</th></tr>
  @foreach($po->items as $it)
  @php($out = $it->quantity - $it->quantity_received)
  <tr><td>{{ $it->product->name }}</td><td>{{ $it->quantity }}</td><td>{{ $it->quantity_received }}</td><td>{{ $out }}</td>
   <td>@if($out > 0)<input type="number" min="0" max="{{ $out }}" name="received[{{ $it->id }}]" value="{{ old('received.'.$it->id, $out) }}" style="width:90px">@else<span class="badge green">Complete</span>@endif</td></tr>
  @endforeach
 </table>
 <p><small>You cannot receive more than the outstanding quantity. The PO is marked Delivered once every line is complete.</small></p>
 <p><button>Confirm delivery</button> <a class="btn muted" href="{{ route('deliveries.index') }}">Cancel</a></p>
</div>
</form>
@endsection
