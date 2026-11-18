@extends('layouts.app')
@section('title', 'Daily attendance')
@section('heading', 'Daily Attendance')
@section('page-actions')<a class="btn btn-alt" href="{{ route('attendance.period') }}">Period view</a>@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('attendance.day') }}" class="report-filter">
  {{-- Picking a date loads that day right away (no button needed). --}}
  <label>Date <input type="date" name="date" value="{{ $date }}" required onchange="if (this.value) this.form.submit()"></label>
 </form>
 <form method="POST" action="{{ route('attendance.day.save') }}">
  @csrf
  <input type="hidden" name="work_date" value="{{ $date }}">
  <table class="attendance-table no-fit">
   <thead><tr><th>Employee</th><th>Time in</th><th>Time out</th><th>Overtime hours</th></tr></thead>
   <tbody>
    @foreach($employees as $employee)
     @php($record = $records->get($employee->employee_id))
     <tr>
      <td>{{ $employee->full_name }}<input type="hidden" name="records[{{ $employee->employee_id }}][employee_id]" value="{{ $employee->employee_id }}"></td>
      <td><input type="time" name="records[{{ $employee->employee_id }}][time_in]" value="{{ $record?->time_in ? substr($record->time_in, 0, 5) : '' }}"></td>
      <td><input type="time" name="records[{{ $employee->employee_id }}][time_out]" value="{{ $record?->time_out ? substr($record->time_out, 0, 5) : '' }}"></td>
      <td><input type="number" name="records[{{ $employee->employee_id }}][overtime_hours]" value="{{ $record?->overtime_hours }}" min="0" max="12" step=".25" placeholder="Auto"></td>
     </tr>
    @endforeach
   </tbody>
  </table>
  <p><button type="submit">Save attendance</button></p>
 </form>
</div>
@endsection
