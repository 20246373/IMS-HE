@extends('layouts.app')
@section('title', 'Payroll')
@section('heading', 'Payroll')
@section('page-actions')<a class="btn" href="{{ route('payrolls.create') }}">Run payroll</a>@endsection
@section('content')
<div class="card">
 <table>
  <thead><tr><th>Period</th><th>Status</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($payrolls as $payroll)
    <tr>
     <td>{{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}</td>
     <td>{{ $payroll->status }}</td>
     <td>{{ money($payroll->total_gross) }}</td>
     <td>{{ money($payroll->total_deductions) }}</td>
     <td>{{ money($payroll->total_net) }}</td>
     <td><a class="btn" href="{{ route('payrolls.show', $payroll) }}">Open</a></td>
    </tr>
   @empty
    <tr><td colspan="6">No payrolls found.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $payrolls->links() }}
</div>
@endsection
