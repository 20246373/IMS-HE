@extends('layouts.app')
@section('title', 'End-of-day reconciliation')
@section('content')
<div class="card" style="max-width:640px">
 <h2>End-of-day reconciliation</h2>
 <form method="GET" action="{{ route('ledger.reconcile') }}" class="no-print report-filter">
  <label>Date <input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" required></label>
  <button type="submit">Reconcile</button>
 </form>

 <div class="report-summary">
  <div><strong>Date</strong><span>{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</span></div>
  <div><strong>Completed sales ({{ $result['invoice_count'] }})</strong><span>&#8369;{{ number_format($result['sales_total'], 2) }}</span></div>
  <div><strong>Recorded income</strong><span>&#8369;{{ number_format($result['recorded_income'], 2) }}</span></div>
  <div><strong>Difference</strong><span>&#8369;{{ number_format($result['difference'], 2) }}</span></div>
 </div>

 @if($result['reconciled'])
  <p class="ok">Reconciled: recorded income matches the day's sales.</p>
 @else
  <p class="err">Mismatch: recorded income differs from the day's sales by &#8369;{{ number_format(abs($result['difference']), 2) }}. Check the ledger for missing or duplicate entries.</p>
 @endif

 <p><a class="btn" href="{{ route('ledger.index', ['start_date' => $date, 'end_date' => $date]) }}">View that day's ledger</a></p>
</div>
@endsection
