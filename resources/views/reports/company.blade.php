@extends('layouts.app')
@section('title','Company Report')
@section('content')
<div class="card">
 <h3>Company Report</h3>
 <form method="GET" action="{{ route('reports.company') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}"></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('reports.company') }}" class="btn btn-alt">Clear</a>
 </form>
 <p><strong>Total Income:</strong> {{ number_format($income, 2) }}</p>
 <p><strong>Total Expenses:</strong> {{ number_format($expense, 2) }}</p>
 <p><strong>Net Profit:</strong> {{ number_format($netProfit, 2) }}</p>
 <p><strong>Total Sales Transactions:</strong> {{ $salesCount }}</p>
 <p style="margin-top:1rem">
  <button onclick="window.print()">Print</button>
  <a href="{{ route('reports.export', ['type' => 'company', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-alt">Export CSV</a>
 </p>
</div>
@endsection
