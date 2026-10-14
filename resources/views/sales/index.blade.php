@extends('layouts.app')
@section('title','Sales History')
@section('content')
<div class="card">
 <h3>Sales History</h3>
 <form method="GET" action="{{ route('sales.index') }}" style="margin-bottom:1rem">
  <label>Date <input type="date" name="date" value="{{ request('date') }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('sales.index') }}" class="btn btn-alt">Clear</a>
 </form>
 <table>
  <thead><tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>Total</th><th>Status</th><th>Receipt Printed</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($invoices as $invoice)
    <tr>
     <td>{{ $invoice->invoice_id }}</td>
     <td>{{ $invoice->invoice_date }}</td>
     <td>{{ $invoice->customer?->getFullNameAttribute() ?? 'Walk-in' }}</td>
     <td>{{ number_format($invoice->total_amount, 2) }}</td>
     <td>{{ $invoice->status }}</td>
     <td>{{ $invoice->receipt_printed_at ? $invoice->receipt_printed_at->format('Y-m-d H:i') : 'No' }}</td>
     <td>
      <a href="{{ route('sales.show', $invoice) }}" class="btn">View</a>
      <a href="{{ route('sales.receipt', $invoice) }}" class="btn btn-alt" target="_blank">Print as PDF</a>
     </td>
    </tr>
   @endforeach
  </tbody>
 </table>
 {{ $invoices->links() }}
</div>
@endsection
