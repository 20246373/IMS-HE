@extends('layouts.app')
@section('title','New payroll run')
@section('content')
<div class="card" style="max-width:520px">
 <h3>New payroll run</h3>
 <x-form-note />
 <p class="muted-text">The run is saved as a <strong>Draft</strong> that you can review before finalizing. Each employee with attendance in the period gets a payslip:
  one day worked per attendance record, overtime at 125%, and SSS, PhilHealth, Pag-IBIG and withholding tax taken as
  {{ config('payroll.periods_per_month') == 1 ? 'the full monthly amount' : '1/' . config('payroll.periods_per_month') . ' of the monthly amount' }}
  (rates are set in config/payroll.php). A period cannot overlap an existing payroll run and can be at most 31 days.</p>
 <form method="POST" action="{{ route('payrolls.store') }}">@csrf
  <label>Period start <span class="req">*</span> <input type="date" name="period_start" value="{{ old('period_start') }}" max="{{ today()->toDateString() }}" required></label>
  <label>Period end <span class="req">*</span> <input type="date" name="period_end" value="{{ old('period_end') }}" max="{{ today()->toDateString() }}" required></label>
  <p><button>Run payroll (Draft)</button> <a class="btn muted" href="{{ route('payrolls.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
