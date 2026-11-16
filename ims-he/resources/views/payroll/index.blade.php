@extends('layouts.app')
@section('title', 'Payroll')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Payroll</h2>
  @if(auth()->user()->hasRole('Treasurer'))
   <a class="btn no-print" href="{{ route('payroll.create') }}">New payroll</a>
  @endif
 </div>
 <table>
  <thead><tr><th>Period</th><th>Status</th><th>Gross</th><th>Deductions</th><th>Net pay</th><th>Processed</th><th></th></tr></thead>
  <tbody>
   @forelse($payrolls as $payroll)
   <tr>
    <td>{{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}</td>
    <td>{{ $payroll->status }}</td>
    <td>&#8369;{{ number_format((float) $payroll->total_gross, 2) }}</td>
    <td>&#8369;{{ number_format((float) $payroll->total_deductions, 2) }}</td>
    <td>&#8369;{{ number_format((float) $payroll->total_net, 2) }}</td>
    <td>{{ $payroll->processor?->label() ?? '—' }}<br><small>{{ $payroll->processed_date?->format('M d, Y g:i A') }}</small></td>
    <td><a class="btn" href="{{ route('payroll.show', $payroll) }}">View</a></td>
   </tr>
   @empty
   <tr><td colspan="7">No payroll batches yet.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $payrolls->links() }}
</div>
@endsection
