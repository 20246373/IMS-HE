@extends('layouts.app')
@section('title', 'Recent sales')
@section('heading', 'Recent Sales')
@section('page-actions')<a class="btn" href="{{ route('sales.create') }}">New sale</a>@endsection
@section('content')
<div class="card">
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Total</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($invoices as $invoice)
   <tr>
    <td>#{{ $invoice->invoice_id }}</td><td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
    <td>{{ $invoice->customer?->display_name ?: 'Walk-in customer' }}</td>
    <td>{{ $invoice->employee?->full_name ?: 'System' }}</td><td>{{ money($invoice->total_amount) }}</td>
    <td><a class="btn" href="{{ route('sales.show', $invoice) }}">Receipt</a></td>
   </tr>
   @empty
   <tr><td colspan="6">No in-store sales found.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $invoices->links() }}
</div>
@endsection
