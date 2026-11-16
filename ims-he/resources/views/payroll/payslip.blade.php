@extends('layouts.app')
@section('title', 'Payslip')
@section('content')
@php $emp = $payslip->employee; $payroll = $payslip->payroll; @endphp
<div class="card" id="payslip" style="max-width:640px">
 <h2>Payslip</h2>
 <p>
  <strong>Ibay Enterprises Corporation</strong><br>
  Employee: {{ $emp?->full_name ?? 'Employee #' . $payslip->employee_id }}<br>
  Position: {{ $emp?->position ?? '—' }} &middot; Daily rate: &#8369;{{ number_format((float) ($emp?->daily_rate ?? 0), 2) }}<br>
  Period: {{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}<br>
  Payroll #{{ $payroll->payroll_id }} ({{ $payroll->status }})
 </p>
 <table>
  <tr><th>Earnings</th><th class="amount">Amount</th></tr>
  <tr><td>Days worked: {{ number_format((float) $payslip->days_worked, 2) }} &times; &#8369;{{ number_format((float) ($emp?->daily_rate ?? 0), 2) }}</td><td class="amount">&#8369;{{ number_format((float) $payslip->days_worked * (float) ($emp?->daily_rate ?? 0), 2) }}</td></tr>
  <tr><td>Overtime: {{ number_format((float) $payslip->overtime_hours, 2) }} hrs</td><td class="amount">&#8369;{{ number_format((float) $payslip->gross_pay - (float) $payslip->days_worked * (float) ($emp?->daily_rate ?? 0), 2) }}</td></tr>
  <tr><th>Gross pay</th><th class="amount">&#8369;{{ number_format((float) $payslip->gross_pay, 2) }}</th></tr>
  <tr><th>Deductions</th><th class="amount">Amount</th></tr>
  <tr><td>SSS</td><td class="amount">&#8369;{{ number_format((float) $payslip->sss_deduction, 2) }}</td></tr>
  <tr><td>PhilHealth</td><td class="amount">&#8369;{{ number_format((float) $payslip->philhealth_deduction, 2) }}</td></tr>
  <tr><td>Pag-IBIG</td><td class="amount">&#8369;{{ number_format((float) $payslip->pagibig_deduction, 2) }}</td></tr>
  <tr><td>Withholding tax</td><td class="amount">&#8369;{{ number_format((float) $payslip->tax_deduction, 2) }}</td></tr>
  <tr><td>Other deductions</td><td class="amount">&#8369;{{ number_format((float) $payslip->other_deductions, 2) }}</td></tr>
  <tr><th>Total deductions</th><th class="amount">&#8369;{{ number_format((float) $payslip->total_deductions, 2) }}</th></tr>
  <tr><th>Net pay</th><th class="amount">&#8369;{{ number_format((float) $payslip->net_pay, 2) }}</th></tr>
 </table>
 <p class="no-print">
  <button type="button" onclick="window.print()">Print payslip</button>
  <a class="btn" href="{{ route('payroll.payslip.pdf', [$payroll, $payslip]) }}">Download PDF</a>
  <a class="btn" href="{{ route('payroll.show', $payroll) }}">Back to payroll</a>
 </p>
</div>
@endsection
