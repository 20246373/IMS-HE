@extends('layouts.app')
@section('title', 'Sales report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Sales report</h2>
  <a class="btn no-print" href="{{ route('reports.sales.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}">Save as PDF</a>
 </div>
 <form method="GET" action="{{ route('reports.sales') }}" class="no-print report-filter">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}" required></label>
  <button type="submit">Apply filter</button>
 </form>
 <div class="report-summary">
  <div><strong>Total revenue</strong><span>&#8369;{{ number_format((float) $report['totalRevenue'], 2) }}</span></div>
  <div><strong>Total transactions</strong><span>{{ number_format($report['totalTransactions']) }}</span></div>
 </div>
 <h3>Completed sales</h3>
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Total</th></tr></thead>
  <tbody>
   @forelse($report['sales'] as $sale)
   <tr>
    <td>#{{ $sale->invoice_id }}</td>
    <td>{{ \Illuminate\Support\Carbon::parse($sale->invoice_date)->format('M d, Y') }}</td>
    <td>{{ trim(($sale->first_name ?? '') . ' ' . ($sale->last_name ?? '')) ?: 'Walk-in customer' }}</td>
    <td>&#8369;{{ number_format((float) $sale->total_amount, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="4">No completed sales for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection
