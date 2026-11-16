@extends('layouts.app')
@section('title','Payslip - '.$payslip->employee->full_name)
@section('content')
<div class="card">
 <p class="noprint row">
  <button type="button" onclick="window.print()">Print</button>
  <a class="btn" href="{{ route('payslips.pdf', $payslip->payslip_id) }}">Download PDF</a>
  <a class="btn muted" href="{{ route('payrolls.show', $payslip->payroll_id) }}">Back to payroll</a>
 </p>
 @include('payslips._style')
 @include('payslips._slip')
</div>
@endsection
