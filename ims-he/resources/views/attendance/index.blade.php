@extends('layouts.app')
@section('title', 'Attendance')
@section('content')
@php $canLog = auth()->user()->hasRole('Treasurer', 'Store Supervisor'); @endphp
<div class="card">
 <h2>Attendance</h2>

 @if($canLog)
 <div class="report-filter" style="align-items:flex-start">
  <form method="POST" action="{{ route('attendance.clock-in') }}" class="report-filter">
   @csrf
   <strong>Clock in</strong>
   <label>Employee
    <select name="employee_id" required>
     @foreach($employees as $emp)
      <option value="{{ $emp->employee_id }}">{{ $emp->full_name }} ({{ $emp->position }})</option>
     @endforeach
    </select>
   </label>
   <label>Date <small class="optional">(today)</small>
    <input type="date" name="work_date" max="{{ today()->toDateString() }}">
   </label>
   <button type="submit">Clock in</button>
  </form>

  <form method="POST" action="{{ route('attendance.store') }}" class="report-filter">
   @csrf
   <strong>Log a day</strong>
   <label>Employee
    <select name="employee_id" required>
     @foreach($employees as $emp)
      <option value="{{ $emp->employee_id }}">{{ $emp->full_name }}</option>
     @endforeach
    </select>
   </label>
   <label>Date <input type="date" name="work_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></label>
   <label>In <input type="time" name="time_in" required></label>
   <label>Out <input type="time" name="time_out" required></label>
   <button type="submit">Log</button>
  </form>
 </div>
 @endif

 <form method="GET" action="{{ route('attendance.index') }}" class="no-print report-filter">
  <label>Employee
   <select name="employee_id">
    <option value="">All</option>
    @foreach($employees as $emp)
     <option value="{{ $emp->employee_id }}" @selected(($filters['employee_id'] ?? '') == $emp->employee_id)>{{ $emp->full_name }}</option>
    @endforeach
   </select>
  </label>
  <label>Date <input type="date" name="date" value="{{ $filters['date'] ?? '' }}"></label>
  <button type="submit">Filter</button>
 </form>

 <table>
  <thead><tr><th>Date</th><th>Employee</th><th>Time in</th><th>Time out</th><th>Overtime (hrs)</th><th></th></tr></thead>
  <tbody>
   @forelse($records as $record)
   <tr>
    <td>{{ $record->work_date->format('M d, Y') }}</td>
    <td>{{ $record->employee?->full_name ?? 'Employee #' . $record->employee_id }}</td>
    <td>{{ \Illuminate\Support\Carbon::parse($record->time_in)->format('g:i A') }}</td>
    <td>{{ $record->time_out ? \Illuminate\Support\Carbon::parse($record->time_out)->format('g:i A') : '—' }}</td>
    <td>{{ number_format((float) $record->overtime_hours, 2) }}</td>
    <td class="no-print">
     @if($canLog)
      @if($record->time_out === null)
       <form method="POST" action="{{ route('attendance.clock-out', $record) }}" class="inline">
        @csrf <button type="submit">Clock out</button>
       </form>
      @else
       <details>
        <summary>Edit times</summary>
        <form method="POST" action="{{ route('attendance.update', $record) }}" class="report-filter">
         @csrf @method('PATCH')
         <input type="time" name="time_in" value="{{ \Illuminate\Support\Carbon::parse($record->time_in)->format('H:i') }}" required>
         <input type="time" name="time_out" value="{{ \Illuminate\Support\Carbon::parse($record->time_out)->format('H:i') }}" required>
         <button type="submit">Save</button>
        </form>
       </details>
      @endif
     @endif
    </td>
   </tr>
   @empty
   <tr><td colspan="6">No attendance records.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $records->links() }}
</div>
@endsection
