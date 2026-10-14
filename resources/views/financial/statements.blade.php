@extends('layouts.app')
@section('title','Financial Statements')
@section('content')
<div class="card">
 <h3>Financial Statement</h3>
 <form method="GET" action="{{ route('financial.statements') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ request('start_date') }}"></label>
  <label>To <input type="date" name="end_date" value="{{ request('end_date') }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('financial.statements') }}" class="btn btn-alt">Clear</a>
 </form>
 <table>
  <thead><tr><th>Date</th><th>Income</th><th>Expenses</th><th>Net</th></tr></thead>
  <tbody>
   @php($netTotal = 0)
   @php($allDates = $income->pluck('entry_date')->merge($expense->pluck('entry_date'))->unique()->sort())
   @foreach($allDates as $date)
    @php($inc = $income->firstWhere('entry_date', $date))
    @php($exp = $expense->firstWhere('entry_date', $date))
    @php($net = ($inc->total ?? 0) - ($exp->total ?? 0))
    @php($netTotal += $net)
    <tr>
     <td>{{ $date }}</td>
     <td>{{ number_format($inc->total ?? 0, 2) }}</td>
     <td>{{ number_format($exp->total ?? 0, 2) }}</td>
     <td>{{ number_format($net, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
  <tfoot><tr><th colspan="3">Total Net</th><th>{{ number_format($netTotal, 2) }}</th></tr></tfoot>
 </table>
 <p style="margin-top:1rem"><a href="{{ route('financial.export', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn">Export CSV</a></p>
</div>
@endsection
