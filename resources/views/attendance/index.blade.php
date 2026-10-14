@extends('layouts.app')
@section('title','Attendance')
@section('content')
<div class="card">
 <h3>Attendance Management</h3>
 <p><a href="{{ route('attendance.create') }}" class="btn">Record Attendance</a></p>
 <form method="GET" action="{{ route('attendance.index') }}" style="margin-bottom:1rem">
  <label>Date <input type="date" name="date" value="{{ request('date') }}"></label>
  <label>Employee <select name="employee_id"><option value="">All</option>@foreach($employees as $e)<option value="{{ $e->employee_id }}" {{ request('employee_id') == $e->employee_id ? 'selected' : '' }}>{{ $e->last_name }}, {{ $e->first_name }}</option>@endforeach</select></label>
  <button type="submit">Filter</button>
  <a href="{{ route('attendance.index') }}" class="btn btn-alt">Clear</a>
 </form>
 <table>
  <thead><tr><th>Date</th><th>Employee</th><th>Time In</th><th>Time Out</th><th>Overtime</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($attendances as $attendance)
    <tr>
     <td>{{ $attendance->work_date }}</td>
     <td>{{ $attendance->employee->last_name }}, {{ $attendance->employee->first_name }}</td>
     <td>{{ $attendance->time_in }}</td>
     <td>{{ $attendance->time_out ?? '-' }}</td>
     <td>{{ $attendance->overtime_hours }}</td>
     <td><a href="{{ route('attendance.edit', $attendance) }}" class="btn">Edit</a></td>
    </tr>
   @endforeach
  </tbody>
 </table>
 {{ $attendances->links() }}
</div>
@endsection
