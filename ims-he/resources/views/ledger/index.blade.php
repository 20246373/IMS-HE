@extends('layouts.app')
@section('title', 'Financial ledger')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Financial ledger</h2>
  <span class="no-print">
   <a class="btn" href="{{ route('ledger.reconcile') }}">End-of-day reconciliation</a>
   @if(auth()->user()->hasRole('President', 'Treasurer'))
    <a class="btn" href="{{ route('ledger.create') }}">Log income/expense</a>
   @endif
  </span>
 </div>

 <form method="GET" action="{{ route('ledger.index') }}" class="no-print report-filter">
  <label>From <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></label>
  <label>To <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></label>
  <label>Type
   <select name="entry_type">
    <option value="">All</option>
    <option value="Income" @selected(($filters['entry_type'] ?? '') === 'Income')>Income</option>
    <option value="Expense" @selected(($filters['entry_type'] ?? '') === 'Expense')>Expense</option>
   </select>
  </label>
  <button type="submit">Apply filter</button>
 </form>

 <div class="report-summary">
  <div><strong>Income</strong><span>&#8369;{{ number_format($income, 2) }}</span></div>
  <div><strong>Expenses</strong><span>&#8369;{{ number_format($expenses, 2) }}</span></div>
  <div><strong>Balance</strong><span>&#8369;{{ number_format($balance, 2) }}</span></div>
  @if($cashFlow)
   <div><strong>Net cash flow</strong><span>&#8369;{{ number_format($cashFlow['net'], 2) }}</span></div>
  @endif
 </div>

 @if($cashFlow)
  <p><small>Cash flow for {{ $filters['start_date'] }} to {{ $filters['end_date'] }}:
   income &#8369;{{ number_format($cashFlow['income'], 2) }},
   expenses &#8369;{{ number_format($cashFlow['expenses'], 2) }},
   net &#8369;{{ number_format($cashFlow['net'], 2) }}.</small></p>
 @endif

 <table>
  <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Reference</th><th>Recorded by</th><th class="amount">Amount</th></tr></thead>
  <tbody>
   @forelse($entries as $entry)
   <tr>
    <td>{{ $entry->entry_date->format('M d, Y') }}</td>
    <td>{{ $entry->entry_type }}</td>
    <td>{{ $entry->description }}</td>
    <td>
     @if($entry->reference_type)
      {{ $entry->reference_type }} #{{ $entry->reference_id }}
     @else
      Manual
     @endif
    </td>
    <td>{{ $entry->recorder?->label() ?? 'System' }}</td>
    <td class="amount">&#8369;{{ number_format((float) $entry->amount, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="6">No ledger entries for this filter.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $entries->links() }}
</div>
@endsection
