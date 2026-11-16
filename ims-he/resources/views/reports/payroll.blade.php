@extends('layouts.app')
@section('title', 'Payroll report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Payroll report</h2>
  @if($payroll)
   <a class="btn no-print" href="{{ route('reports.payroll.download', ['payroll_id' => $payroll->payroll_id]) }}">Save as PDF</a>
  @endif
 </div>

 <form method="GET" action="{{ route('reports.payroll') }}" class="no-print report-filter">
  <label>Payroll batch
   <select name="payroll_id">
    @foreach($payrolls as $batch)
     <option value="{{ $batch->payroll_id }}" @selected($payroll?->payroll_id === $batch->payroll_id)>
      #{{ $batch->payroll_id }} — {{ $batch->period_start->format('M d, Y') }} to {{ $batch->period_end->format('M d, Y') }} ({{ $batch->status }})
     </option>
    @endforeach
   </select>
  </label>
  <button type="submit">View</button>
 </form>

 @if($payroll)
  <p>
   Payroll #{{ $payroll->payroll_id }} — {{ $payroll->status }}
   &middot; {{ $payroll->period_start->format('M d, Y') }} to {{ $payroll->period_end->format('M d, Y') }}
   &middot; Processed by {{ $payroll->processor?->label() ?? '—' }}
  </p>
  <div class="report-summary">
   <div><strong>Total gross</strong><span>&#8369;{{ number_format((float) $payroll->total_gross, 2) }}</span></div>
   <div><strong>Total deductions</strong><span>&#8369;{{ number_format((float) $payroll->total_deductions, 2) }}</span></div>
   <div><strong>Total net</strong><span>&#8369;{{ number_format((float) $payroll->total_net, 2) }}</span></div>
  </div>
  <table>
   <thead><tr><th>Employee</th><th>Days</th><th>OT hrs</th><th>Gross</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>Tax</th><th>Other</th><th>Net pay</th></tr></thead>
   <tbody>
    @foreach($payroll->payslips as $payslip)
    <tr>
     <td>{{ $payslip->employee?->full_name ?? 'Employee #' . $payslip->employee_id }}</td>
     <td>{{ number_format((float) $payslip->days_worked, 2) }}</td>
     <td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->gross_pay, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->sss_deduction, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->philhealth_deduction, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->pagibig_deduction, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->tax_deduction, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->other_deductions, 2) }}</td>
     <td>&#8369;{{ number_format((float) $payslip->net_pay, 2) }}</td>
    </tr>
    @endforeach
   </tbody>
  </table>
 @else
  <p>No payroll batches yet. Compute one under Payroll.</p>
 @endif
</div>
@endsection
