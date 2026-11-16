@extends('layouts.app')
@section('title','Payroll report #'.$payroll->payroll_id)
@section('content')
<div class="card">
 <p class="noprint row">
  <button type="button" onclick="window.print()">Print</button>
  <a class="btn" href="{{ route('payrolls.report.pdf', $payroll->payroll_id) }}">Download PDF</a>
  <a class="btn muted" href="{{ route('payrolls.show', $payroll->payroll_id) }}">Back to payroll</a>
 </p>
 @include('payslips._style')
 @include('payrolls._report')
</div>
@endsection
