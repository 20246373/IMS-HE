@extends('layouts.app')
@section('title', 'New payroll')
@section('content')
<div class="card" style="max-width:560px">
 <h2>Compute payroll</h2>
 <p><small>Payslips are computed from attendance records in the period. Deductions use the rates in <code>config/payroll.php</code>.</small></p>
 <form method="POST" action="{{ route('payroll.store') }}">
  @csrf
  <label>Period start <input type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required></label>
  <label>Period end <input type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required></label>
  <p>
   <button type="submit">Compute payroll</button>
   <a class="btn" href="{{ route('payroll.index') }}">Cancel</a>
  </p>
 </form>
</div>
@endsection
