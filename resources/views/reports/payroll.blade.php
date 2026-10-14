@extends('layouts.app')
@section('title','Payroll Report')
@section('content')
<div class="card">
 <h3>Payroll Report</h3>
 <form method="GET" action="{{ route('reports.payroll') }}" style="margin-bottom:1rem">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}"></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}"></label>
  <button type="submit">Filter</button>
  <a href="{{ route('reports.payroll') }}" class="btn btn-alt">Clear</a>
 </form>
 <p><strong>Total Gross Pay:</strong> {{ number_format($totalGross, 2) }}</p>
 <p><strong>Total Net Pay:</strong> {{ number_format($totalNet, 2) }}</p>
 <table>
  <thead><tr><th>Period</th><th>Status</th><th>Total Gross</th><th>Total Net</th><th>Processed By</th></tr></thead>
  <tbody>
   @foreach($payrolls as $payroll)
    <tr>
     <td>{{ $payroll->period_start }} to {{ $payroll->period_end }}</td>
     <td>{{ $payroll->status }}</td>
     <td>{{ number_format($payroll->total_gross, 2) }}</td>
     <td>{{ number_format($payroll->total_net, 2) }}</td>
     <td>{{ $payroll->processor?->username }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <p style="margin-top:1rem"><button onclick="window.print()">Print</button></p>
</div>
@endsection
