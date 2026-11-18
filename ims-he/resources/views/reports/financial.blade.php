@extends('layouts.app')
@section('title', 'Financial report')
@section('heading', 'Financial Report')
@section('page-actions')<a class="btn" href="{{ route('reports.financial.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}">Save as PDF</a>@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('reports.financial') }}" class="report-filter">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}" required></label>
  <button type="submit">Apply filter</button>
 </form>
 <div class="report-summary"><div><strong>Income</strong><span>{{ money($report['totalIncome']) }}</span></div><div><strong>Expense</strong><span>{{ money($report['totalExpense']) }}</span></div><div><strong>Net</strong><span>{{ money($report['totalNet']) }}</span></div></div>
 <table><thead><tr><th>Date</th><th>Income</th><th>Expense</th><th>Net</th></tr></thead><tbody>
 @foreach($report['days'] as $day)<tr><td>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M d, Y') }}</td><td>{{ money($day['income']) }}</td><td>{{ money($day['expense']) }}</td><td>{{ money($day['net']) }}</td></tr>@endforeach
 </tbody></table>
</div>
@endsection
