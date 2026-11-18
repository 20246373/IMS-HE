@extends('layouts.app')
@section('title', 'Ledger')
@section('heading', 'Ledger')
@section('page-actions')<a class="btn" href="{{ route('ledger.create') }}">New entry</a>@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('ledger.index') }}" class="report-filter">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}" required></label>
  <label>Type <select name="entry_type"><option value="">All</option><option value="Income" @selected($type === 'Income')>Income</option><option value="Expense" @selected($type === 'Expense')>Expense</option></select></label>
  <button type="submit">Apply filter</button>
 </form>
 <div class="report-summary">
  <div><strong>Income</strong><span>{{ money($totals['Income']) }}</span></div>
  <div><strong>Expense</strong><span>{{ money($totals['Expense']) }}</span></div>
  <div><strong>Net</strong><span>{{ money($totals['Net']) }}</span></div>
 </div>
 <table>
  <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Reference</th><th>Recorded by</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($entries as $entry)
   <tr>
    <td>{{ $entry->entry_date->format('M d, Y') }}</td><td>{{ $entry->entry_type }}</td><td>{{ money($entry->amount) }}</td>
    <td>{{ $entry->description }}</td><td>{{ $entry->reference_type ? $entry->reference_type.' #'.$entry->reference_id : '—' }}</td>
    <td>{{ $entry->recorder?->displayName() ?? 'System' }}</td>
    <td><form method="POST" action="{{ route('ledger.reverse', $entry) }}" class="inline">@csrf @method('POST')<button class="danger" onclick="return confirm('Reverse this entry?')">Reverse</button></form></td>
   </tr>
   @empty
   <tr><td colspan="7">No ledger entries for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $entries->links() }}
</div>
@endsection
