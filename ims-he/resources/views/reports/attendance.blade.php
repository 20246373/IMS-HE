@extends('layouts.app')
@section('title', 'Attendance report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Attendance report</h2>
  <span class="no-print">
   <button type="button" onclick="window.print()">Print</button>
   <a class="btn" href="{{ route('reports.attendance.download', ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]) }}">Save as PDF</a>
  </span>
 </div>
 <form method="GET" action="{{ route('reports.attendance') }}" class="no-print report-filter">
  <label>From <input type="date" name="start_date" value="{{ $filters['start_date'] }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $filters['end_date'] }}" required></label>
  <button type="submit">Apply filter</button>
 </form>
 <div class="report-summary">
  <div><strong>Total days worked</strong><span>{{ number_format($report['totalDays']) }}</span></div>
  <div><strong>Total overtime (hrs)</strong><span>{{ number_format($report['totalOvertime'], 2) }}</span></div>
 </div>
 <table>
  <thead><tr><th>Employee</th><th>Position</th><th>Days</th><th>Clocked out</th><th>Overtime (hrs)</th></tr></thead>
  <tbody>
   @forelse($report['rows'] as $row)
   <tr>
    <td>{{ trim($row->first_name . ' ' . $row->last_name) }}</td>
    <td>{{ $row->position }}</td>
    <td>{{ number_format($row->days_worked) }}</td>
    <td>{{ number_format($row->days_clocked_out) }}</td>
    <td>{{ number_format((float) $row->overtime_hours, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="5">No attendance records for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection
