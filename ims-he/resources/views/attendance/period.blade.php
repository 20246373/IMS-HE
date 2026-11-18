@extends('layouts.app')
@section('title', 'Attendance period')
@section('heading', 'Attendance Period')
@section('page-actions')<a class="btn btn-alt" href="{{ route('attendance.day') }}">Daily view</a>@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('attendance.period') }}" class="report-filter">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}" required></label>
  <x-select label="Employee" name="employee_id" :required="false">
   <option value="">All employees</option>
   @foreach($employees as $employee)
    <option value="{{ $employee->employee_id }}" @selected((string) $employeeId === (string) $employee->employee_id)>{{ $employee->full_name }}</option>
   @endforeach
  </x-select>
  <button type="submit">Apply filter</button>
 </form>
 <table>
  <thead><tr><th>Date</th><th>Employee</th><th>Time in</th><th>Time out</th><th>Overtime</th></tr></thead>
  <tbody>
   @forelse($records as $record)
    <tr>
     <td>{{ $record->work_date->format('M d, Y') }}</td>
     <td>{{ $record->employee->full_name }}</td>
     <td>{{ $record->time_in }}</td>
     <td>{{ $record->time_out ?: '—' }}</td>
     <td>{{ number_format((float) $record->overtime_hours, 2) }} hours</td>
    </tr>
   @empty
    <tr><td colspan="5">No attendance records found.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $records->links() }}
</div>
@endsection
