@extends('layouts.app')
@section('title','Add attendance')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Add attendance record</h3>
 <x-form-note />
 <p class="muted-text">Use this for a day the employee did not clock in. Each employee can have only one record per date. Overtime is worked out automatically (hours beyond 8).</p>
 <form method="POST" action="{{ route('attendance.store') }}">@csrf
  <label>Employee <span class="req">*</span>
   <select name="employee_id" required>
    <option value="">-</option>
    @foreach($employees as $e)
     <option value="{{ $e->employee_id }}" @selected((string) old('employee_id') === (string) $e->employee_id)>{{ $e->full_name }} - {{ $e->position }}</option>
    @endforeach
   </select></label>
  <label>Date <span class="req">*</span> <input type="date" name="work_date" value="{{ old('work_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
  <label>Time in <span class="req">*</span> <input type="time" name="time_in" value="{{ old('time_in') }}" required></label>
  <label>Time out <span class="opt">(optional)</span> <input type="time" name="time_out" value="{{ old('time_out') }}"></label>
  <p><button>Save record</button> <a class="btn muted" href="{{ route('attendance.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
