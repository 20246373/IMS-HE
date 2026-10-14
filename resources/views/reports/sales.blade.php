@extends('layouts.app')
@section('title','Sales Report')
@section('content')
<div class="card">
 <h3>Sales Report</h3>
 <form method="GET" action="{{ route('reports.sales') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}"></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('reports.sales') }}" class="btn btn-alt">Clear</a>
 </form>
 <p><strong>Total Sales:</strong> {{ number_format($totalSales, 2) }}</p>
 <p><strong>Total Transactions:</strong> {{ $totalTransactions }}</p>
 <table>
  <thead><tr><th>Date</th><th>Invoice #</th><th>Customer</th><th>Employee</th><th>Total</th><th>Status</th></tr></thead>
  <tbody>
   @foreach($sales as $sale)
    <tr>
     <td>{{ $sale->invoice_date }}</td>
     <td>{{ $sale->invoice_id }}</td>
     <td>{{ $sale->customer?->getFullNameAttribute() ?? 'Walk-in' }}</td>
     <td>{{ $sale->employee?->getFullNameAttribute() ?? 'N/A' }}</td>
     <td>{{ number_format($sale->total_amount, 2) }}</td>
     <td>{{ $sale->status }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <p style="margin-top:1rem">
  <button onclick="window.print()">Print</button>
  @if(auth()->user()->hasRole('Admin'))
   <a href="{{ route('reports.export', ['type' => 'sales', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-alt">Export CSV</a>
  @endif
 </p>
</div>
@endsection
