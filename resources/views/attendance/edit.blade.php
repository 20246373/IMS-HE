@extends('layouts.app')
@section('title','Edit Attendance')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit Attendance</h3>
 <form method="POST" action="{{ route('attendance.update', $attendance) }}">@csrf @method('PUT')
  <label>Employee <select name="employee_id" required><option value="">Select</option>@foreach($employees as $e)<option value="{{ $e->employee_id }}" {{ $attendance->employee_id == $e->employee_id ? 'selected' : '' }}>{{ $e->last_name }}, {{ $e->first_name }}</option>@endforeach</select></label>
  <label>Date <input type="date" name="work_date" value="{{ $attendance->work_date }}" required></label>
  <label>Time In <input type="time" name="time_in" value="{{ $attendance->time_in }}" required></label>
  <label>Time Out <input type="time" name="time_out" value="{{ $attendance->time_out }}"></label>
  <label>Overtime Hours <input type="number" step="0.5" name="overtime_hours" value="{{ $attendance->overtime_hours }}"></label>
  <p><button>Save</button> <a href="{{ route('attendance.index') }}" class="btn btn-alt">Cancel</a></p>
 </form>
</div>
@endsection
