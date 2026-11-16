@extends('layouts.app')
@section('title','Payroll #'.$payroll->payroll_id)
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Payroll #{{ $payroll->payroll_id }}
   <span class="badge {{ $payroll->isFinal() ? 'final' : 'draft' }}">{{ $payroll->status }}</span></h3>
  <span class="noprint">
   <a class="btn" href="{{ route('payrolls.report', $payroll->payroll_id) }}">Payroll report</a>
   <a class="btn muted" href="{{ route('payrolls.index') }}">Back</a>
  </span>
 </div>
 <p>{{ $payroll->period_start->format('M d, Y') }} &ndash; {{ $payroll->period_end->format('M d, Y') }}
  &middot; run by {{ $payroll->processor?->employee?->full_name ?? $payroll->processor?->username }}
  on {{ $payroll->processed_date?->format('M d, Y g:i A') }}</p>

 @if($payroll->isDraft())
  <p class="note">This is a draft. Check the figures below. Finalizing locks the payroll for good (it can no longer be changed)
   and records an expense of <strong>@peso($payroll->total_gross)</strong> in the ledger.</p>
  @if(auth()->user()->hasRole('Treasurer'))
  <div class="row">
   <form method="POST" action="{{ route('payrolls.finalize', $payroll->payroll_id) }}" class="inline"
         onsubmit="return confirm('Finalize this payroll? It cannot be changed afterwards.')">@csrf<button>Finalize payroll</button></form>
   <form method="POST" action="{{ route('payrolls.destroy', $payroll->payroll_id) }}" class="inline"
         onsubmit="return confirm('Delete this draft? You can run the period again afterwards.')">@csrf @method('DELETE')<button class="danger">Delete draft</button></form>
  </div>
  @endif
 @else
  <p class="muted-text">This payroll is finalized and cannot be changed.</p>
 @endif

 <div style="overflow-x:auto">
 <table>
  <tr><th>Employee</th><th class="num">Days</th><th class="num">OT hrs</th><th class="num">Gross pay</th><th class="num">SSS</th><th class="num">PhilHealth</th><th class="num">Pag-IBIG</th><th class="num">Tax</th><th class="num">Total deductions</th><th class="num">Net pay</th><th></th></tr>
  @foreach($payroll->payslips as $s)
  <tr>
   <td>{{ $s->employee->full_name }}</td>
   <td class="num">{{ rtrim(rtrim(number_format($s->days_worked, 2), '0'), '.') }}</td>
   <td class="num">{{ number_format($s->overtime_hours, 2) }}</td>
   <td class="num">@peso($s->gross_pay)</td>
   <td class="num">@peso($s->sss_deduction)</td>
   <td class="num">@peso($s->philhealth_deduction)</td>
   <td class="num">@peso($s->pagibig_deduction)</td>
   <td class="num">@peso($s->tax_deduction)</td>
   <td class="num">@peso($s->total_deductions)</td>
   <td class="num"><strong>@peso($s->net_pay)</strong></td>
   <td><a href="{{ route('payslips.show', $s->payslip_id) }}">Payslip</a></td>
  </tr>
  @endforeach
  <tr>
   <td colspan="3"><strong>Totals</strong></td>
   <td class="num"><strong>@peso($payroll->total_gross)</strong></td>
   <td class="num">@peso($payroll->payslips->sum('sss_deduction'))</td>
   <td class="num">@peso($payroll->payslips->sum('philhealth_deduction'))</td>
   <td class="num">@peso($payroll->payslips->sum('pagibig_deduction'))</td>
   <td class="num">@peso($payroll->payslips->sum('tax_deduction'))</td>
   <td class="num"><strong>@peso($payroll->total_deductions)</strong></td>
   <td class="num"><strong>@peso($payroll->total_net)</strong></td>
   <td></td>
  </tr>
 </table>
 </div>
</div>
@endsection
