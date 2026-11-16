@extends('layouts.app')
@section('title','Correct attendance')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Correct attendance</h3>
 <x-form-note />
 <p class="muted-text">Overtime is recalculated from the times you save (hours beyond 8). Employee and date cannot be changed.</p>
 <form method="POST" action="{{ route('attendance.update', $attendance->attendance_id) }}">@csrf @method('PUT')
  <label>Employee <span class="opt">(read-only)</span> <input value="{{ $attendance->employee->full_name }}" readonly></label>
  <label>Date <span class="opt">(read-only)</span> <input value="{{ $attendance->work_date->format('M d, Y') }}" readonly></label>
  <label>Time in <span class="req">*</span> <input type="time" name="time_in" value="{{ old('time_in', substr($attendance->time_in, 0, 5)) }}" required></label>
  <label>Time out <span class="opt">(optional)</span> <input type="time" name="time_out" value="{{ old('time_out', $attendance->time_out ? substr($attendance->time_out, 0, 5) : '') }}"></label>
  <p><button>Save correction</button> <a class="btn muted" href="{{ route('attendance.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
