@extends('layouts.app')
@section('title','Record Attendance')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Record Attendance</h3>
 <form method="POST" action="{{ route('attendance.store') }}">@csrf
  <label>Employee <select name="employee_id" required><option value="">Select</option>@foreach($employees as $e)<option value="{{ $e->employee_id }}">{{ $e->last_name }}, {{ $e->first_name }}</option>@endforeach</select></label>
  <label>Date <input type="date" name="work_date" value="{{ now()->toDateString() }}" required></label>
  <label>Time In <input type="time" name="time_in" required></label>
  <label>Time Out <input type="time" name="time_out"></label>
  <label>Overtime Hours <input type="number" step="0.5" name="overtime_hours" value="0"></label>
  <p><button>Save</button> <a href="{{ route('attendance.index') }}" class="btn btn-alt">Cancel</a></p>
 </form>
</div>
@endsection
