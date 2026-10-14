@extends('layouts.app')
@section('title','Cash Flow Report')
@section('content')
<div class="card">
 <h3>Cash Flow Report</h3>
 <form method="GET" action="{{ route('financial.cash-flow') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ request('start_date') }}"></label>
  <label>To <input type="date" name="end_date" value="{{ request('end_date') }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('financial.cash-flow') }}" class="btn btn-alt">Clear</a>
 </form>
 <table>
  <thead><tr><th>Date</th><th>Inflows</th><th>Outflows</th><th>Net Flow</th></tr></thead>
  <tbody>
   @php($allDates = $cashFlow->pluck('entry_date')->unique()->sort())
   @php($netTotal = 0)
   @foreach($allDates as $date)
    @php($inflow = $cashFlow->firstWhere('entry_date', $date, 'entry_type', 'income'))
    @php($outflow = $cashFlow->firstWhere('entry_date', $date, 'entry_type', 'expense'))
    @php($net = ($inflow->total ?? 0) - ($outflow->total ?? 0))
    @php($netTotal += $net)
    <tr>
     <td>{{ $date }}</td>
     <td>{{ number_format($inflow->total ?? 0, 2) }}</td>
     <td>{{ number_format($outflow->total ?? 0, 2) }}</td>
     <td>{{ number_format($net, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
  <tfoot><tr><th colspan="3">Total Net Flow</th><th>{{ number_format($netTotal, 2) }}</th></tr></tfoot>
 </table>
 <p style="margin-top:1rem"><a href="{{ route('financial.export', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn">Export CSV</a></p>
</div>
@endsection
