@extends('layouts.app')
@section('title','Payroll Details')
@section('content')
<div class="card">
 <h3>Payroll #{{ $payroll->payroll_id }}</h3>
 <p><strong>Period:</strong> {{ $payroll->period_start }} to {{ $payroll->period_end }}</p>
 <p><strong>Status:</strong> {{ $payroll->status }}</p>
 <p><strong>Total Gross:</strong> {{ number_format($payroll->total_gross, 2) }}</p>
 <p><strong>Total Deductions:</strong> {{ number_format($payroll->total_deductions, 2) }}</p>
 <p><strong>Total Net:</strong> {{ number_format($payroll->total_net, 2) }}</p>
 <h4>Payslips</h4>
 <table>
  <thead><tr><th>Employee</th><th>Days Worked</th><th>Overtime</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($payroll->payslips as $payslip)
    <tr>
     <td>{{ $payslip->employee->last_name }}, {{ $payslip->employee->first_name }}</td>
     <td>{{ $payslip->days_worked }}</td>
     <td>{{ $payslip->overtime_hours }}</td>
     <td>{{ number_format($payslip->gross_pay, 2) }}</td>
     <td>{{ number_format($payslip->total_deductions, 2) }}</td>
     <td>{{ number_format($payslip->net_pay, 2) }}</td>
     <td><a href="{{ route('payslips.show', $payslip) }}" class="btn" target="_blank">Payslip</a></td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <p style="margin-top:1rem"><a href="{{ route('payrolls.index') }}" class="btn btn-alt">Back</a></p>
</div>
@endsection
