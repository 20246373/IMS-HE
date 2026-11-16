@extends('layouts.app')
@section('title', 'Financial report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Financial report</h2>
  <span class="no-print">
   <button type="button" onclick="window.print()">Print</button>
   <a class="btn" href="{{ route('reports.financial.download', ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]) }}">Save as PDF</a>
  </span>
 </div>
 <form method="GET" action="{{ route('reports.financial') }}" class="no-print report-filter">
  <label>From <input type="date" name="start_date" value="{{ $filters['start_date'] }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $filters['end_date'] }}" required></label>
  <button type="submit">Apply filter</button>
 </form>

 <div class="report-summary">
  <div><strong>Income</strong><span>&#8369;{{ number_format($cashFlow['income'], 2) }}</span></div>
  <div><strong>Expenses</strong><span>&#8369;{{ number_format($cashFlow['expenses'], 2) }}</span></div>
  <div><strong>Net cash flow</strong><span>&#8369;{{ number_format($cashFlow['net'], 2) }}</span></div>
 </div>

 <h3>Ledger entries</h3>
 <table>
  <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Reference</th><th>Recorded by</th><th class="amount">Amount</th></tr></thead>
  <tbody>
   @forelse($entries as $entry)
   <tr>
    <td>{{ $entry->entry_date->format('M d, Y') }}</td>
    <td>{{ $entry->entry_type }}</td>
    <td>{{ $entry->description }}</td>
    <td>{{ $entry->reference_type ? $entry->reference_type . ' #' . $entry->reference_id : 'Manual' }}</td>
    <td>{{ $entry->recorder?->label() ?? 'System' }}</td>
    <td class="amount">&#8369;{{ number_format((float) $entry->amount, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="6">No ledger entries for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection
