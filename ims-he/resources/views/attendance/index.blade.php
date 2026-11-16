@extends('layouts.app')
@section('title','Attendance')
@section('content')
@unless($linked)
 <x-alert type="error">Your account is not linked to an employee record, so you cannot clock in. Ask the Administrator to link it on the Accounts screen.</x-alert>
@endunless

@if(! $isManager && $linked)
<div class="card">
 <h3 style="margin-top:0">Today, {{ today()->format('M d, Y') }}</h3>
 @if(! $today)
  <p>You have not clocked in yet.</p>
  <form method="POST" action="{{ route('attendance.clock-in') }}">@csrf<button>Clock in</button></form>
 @elseif($today->time_out === null)
  <p>Clocked in at <strong>{{ \Carbon\Carbon::parse($today->time_in)->format('g:i A') }}</strong>.</p>
  <form method="POST" action="{{ route('attendance.clock-out') }}">@csrf<button>Clock out</button></form>
 @else
  <p>Clocked in {{ \Carbon\Carbon::parse($today->time_in)->format('g:i A') }}, clocked out {{ \Carbon\Carbon::parse($today->time_out)->format('g:i A') }}
   ({{ number_format($today->hoursWorked(), 2) }} hours, overtime {{ number_format($today->overtime_hours, 2) }}). You are done for today.</p>
 @endif
</div>
@endif

<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">{{ $isManager ? 'Attendance records' : 'My attendance' }}</h3>
  @if($isManager)<a class="btn" href="{{ route('attendance.create') }}">+ Add record</a>@endif
 </div>
 <x-form-note />
 <form method="GET" action="{{ route('attendance.index') }}" class="row noprint">
  <label>From <span class="req">*</span> <input type="date" name="from" value="{{ $from }}" required></label>
  <label>To <span class="req">*</span> <input type="date" name="to" value="{{ $to }}" required></label>
  <button>Filter</button>
  <a class="btn muted" href="{{ route('attendance.index') }}">This month</a>
 </form>
 <table>
  <tr><th>Date</th>@if($isManager)<th>Employee</th>@endif<th>Time in</th><th>Time out</th><th class="num">Hours worked</th><th class="num">Overtime (hrs)</th>@if($isManager)<th></th>@endif</tr>
  @forelse($records as $r)
  <tr>
   <td>{{ $r->work_date->format('M d, Y') }}</td>
   @if($isManager)<td>{{ $r->employee->full_name }}</td>@endif
   <td>{{ \Carbon\Carbon::parse($r->time_in)->format('g:i A') }}</td>
   <td>{{ $r->time_out ? \Carbon\Carbon::parse($r->time_out)->format('g:i A') : 'Still clocked in' }}</td>
   <td class="num">{{ $r->hoursWorked() !== null ? number_format($r->hoursWorked(), 2) : '-' }}</td>
   <td class="num">{{ number_format($r->overtime_hours, 2) }}</td>
   @if($isManager)<td><a class="btn" href="{{ route('attendance.edit', $r->attendance_id) }}">Correct</a></td>@endif
  </tr>
  @empty <tr><td colspan="{{ $isManager ? 7 : 5 }}">No attendance records from {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}.</td></tr> @endforelse
 </table>
 {{ $records->links() }}
</div>
@endsection
