@extends('layouts.app')
@section('title','Sales')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Sales</h3>
  <a class="btn" href="{{ route('sales.create') }}">New sale</a>
 </div>
 <table>
  <tr><th>Invoice</th><th>Date</th><th>Channel</th><th>Status</th><th>Total</th><th>Paid</th><th>Change</th><th></th></tr>
  @forelse($invoices as $inv)
  <tr>
   <td>#{{ $inv->invoice_id }}</td>
   <td>{{ $inv->invoice_date->format('M d, Y') }}</td>
   <td>{{ $inv->channel }}</td>
   <td>{{ $inv->status }}</td>
   <td>&#8369;{{ number_format($inv->total_amount, 2) }}</td>
   <td>{{ $inv->payment ? '&#8369;' . number_format($inv->payment->amount_paid, 2) : '-' }}</td>
   <td>{{ $inv->payment ? '&#8369;' . number_format($inv->payment->change_amount, 2) : '-' }}</td>
   <td><a class="btn" href="{{ route('sales.show', $inv->invoice_id) }}">View</a></td>
  </tr>
  @empty <tr><td colspan="8">No sales yet.</td></tr> @endforelse
 </table>
 {{ $invoices->links() }}
</div>
@endsection
