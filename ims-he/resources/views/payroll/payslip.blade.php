@extends('layouts.app')
@section('title', 'Payslip')
@push('head')
<style>@media print{nav, .no-print{display:none!important}body.staff-layout main{width:auto;margin:0;padding:0}.card{box-shadow:none}}</style>
@endpush
@section('content')
<div class="card">
 <div class="report-heading no-print"><h2>Payslip</h2><button onclick="window.print()">Print</button></div>
 <h2>{{ $payslip->employee->full_name }}</h2>
 <p>Payroll period: {{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}</p>
 <table>
  <tr><th>Days worked</th><td>{{ number_format((float) $payslip->days_worked, 2) }}</td></tr>
  <tr><th>Overtime hours</th><td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td></tr>
  <tr><th>Gross pay</th><td>{{ money($payslip->gross_pay) }}</td></tr>
  <tr><th>SSS</th><td>{{ money($payslip->sss_deduction) }}</td></tr>
  <tr><th>PhilHealth</th><td>{{ money($payslip->philhealth_deduction) }}</td></tr>
  <tr><th>Pag-IBIG</th><td>{{ money($payslip->pagibig_deduction) }}</td></tr>
  <tr><th>Withholding tax</th><td>{{ money($payslip->tax_deduction) }}</td></tr>
  <tr><th>Total deductions</th><td>{{ money($payslip->total_deductions) }}</td></tr>
  <tr><th>Net pay</th><td>{{ money($payslip->net_pay) }}</td></tr>
 </table>
</div>
@endsection
