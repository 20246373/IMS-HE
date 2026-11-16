@extends('layouts.app')
@section('title','Payroll')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Payroll runs</h3>
  @if(auth()->user()->hasRole('Treasurer'))<a class="btn" href="{{ route('payrolls.create') }}">+ New payroll run</a>@endif
 </div>
 <div style="overflow-x:auto">
 <table>
  <tr><th>#</th><th>Period</th><th>Status</th><th class="num">Payslips</th><th class="num">Gross pay</th><th class="num">Deductions</th><th class="num">Net pay</th><th>Run by</th><th></th></tr>
  @forelse($payrolls as $p)
  <tr>
   <td>{{ $p->payroll_id }}</td>
   <td>{{ $p->period_start->format('M d, Y') }} &ndash; {{ $p->period_end->format('M d, Y') }}</td>
   <td><span class="badge {{ $p->isFinal() ? 'final' : 'draft' }}">{{ $p->status }}</span></td>
   <td class="num">{{ $p->payslips_count }}</td>
   <td class="num">@peso($p->total_gross)</td>
   <td class="num">@peso($p->total_deductions)</td>
   <td class="num">@peso($p->total_net)</td>
   <td>{{ $p->processor?->employee?->full_name ?? $p->processor?->label() }}</td>
   <td><a class="btn" href="{{ route('payrolls.show', $p->payroll_id) }}">Open</a></td>
  </tr>
  @empty <tr><td colspan="9">No payroll runs yet.</td></tr> @endforelse
 </table>
 </div>
 {{ $payrolls->links() }}
</div>
@endsection
