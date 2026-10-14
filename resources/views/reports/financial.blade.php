@extends('layouts.app')
@section('title','Financial Report')
@section('content')
<div class="card">
 <h3>Financial Report</h3>
 <form method="GET" action="{{ route('reports.financial') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}"></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('reports.financial') }}" class="btn btn-alt">Clear</a>
 </form>
 <p><strong>Total Income:</strong> {{ number_format($income, 2) }}</p>
 <p><strong>Total Expenses:</strong> {{ number_format($expense, 2) }}</p>
 <p><strong>Net:</strong> {{ number_format($net, 2) }}</p>
 <p style="margin-top:1rem"><button onclick="window.print()">Print</button></p>
</div>
@endsection
