@extends('layouts.app')
@section('title', 'Payroll details')
@section('heading', 'Payroll')
@section('page-actions')
   <a class="btn" href="{{ route('payrolls.download', $payroll) }}">Save as PDF</a>
   @if($payroll->status === 'Draft' && auth()->user()->hasPermission('payroll.run'))
    <form method="POST" action="{{ route('payrolls.finalize', $payroll) }}" class="inline">@csrf @method('PATCH')<button>Finalize</button></form>
    <form method="POST" action="{{ route('payrolls.destroy', $payroll) }}" class="inline" onsubmit="return confirm('Delete this draft payroll?')">@csrf @method('DELETE')<button class="danger">Delete draft</button></form>
   @endif
@endsection
@section('content')
<div class="card">
 <p style="margin-top:0">{{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }} · {{ $payroll->status }}</p>
 <div class="report-summary"><div><strong>Gross</strong><span>{{ money($payroll->total_gross) }}</span></div><div><strong>Deductions</strong><span>{{ money($payroll->total_deductions) }}</span></div><div><strong>Net</strong><span>{{ money($payroll->total_net) }}</span></div></div>
 <table>
  <thead><tr><th>Employee</th><th>Days</th><th>OT hours</th><th>Gross</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>Tax</th><th>Deductions</th><th>Net</th></tr></thead>
  <tbody>
   @foreach($payroll->payslips as $payslip)
    <tr>
     <td><a href="{{ route('payrolls.payslip', [$payroll, $payslip]) }}">{{ $payslip->employee->full_name }}</a></td>
     <td>{{ number_format((float) $payslip->days_worked, 2) }}</td><td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td>
     <td>{{ money($payslip->gross_pay) }}</td><td>{{ money($payslip->sss_deduction) }}</td><td>{{ money($payslip->philhealth_deduction) }}</td><td>{{ money($payslip->pagibig_deduction) }}</td><td>{{ money($payslip->tax_deduction) }}</td><td>{{ money($payslip->total_deductions) }}</td><td>{{ money($payslip->net_pay) }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
</div>
@endsection
