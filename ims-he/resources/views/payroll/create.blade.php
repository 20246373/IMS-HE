@extends('layouts.app')
@section('title', 'Run payroll')
@section('heading', 'Run Payroll')
@section('content')
<div class="card">
 <x-required-note />
 <form method="POST" action="{{ route('payrolls.store') }}">
  @csrf
  <div class="form-grid">
   <x-field label="Period start" name="start_date" type="date" value="{{ $startDate }}" />
   <x-field label="Period end" name="end_date" type="date" value="{{ $endDate }}" />
  </div>
  <button type="submit">Create draft</button>
  <a class="btn btn-alt" href="{{ route('payrolls.index') }}">Cancel</a>
 </form>
</div>
@endsection
