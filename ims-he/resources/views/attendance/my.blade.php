@extends('layouts.app')
@section('title', 'My attendance')
@section('content')
<div class="card">
 <h2>My attendance</h2>
 @if(!$employee)
  <x-alert type="warning" title="Employee record not linked">Your account has no employee record linked yet. Ask an Administrator to link it.</x-alert>
 @else
  <p><strong>{{ $employee->full_name }}</strong> · {{ today()->format('M d, Y') }}</p>
  <table>
   <tr><th>Clock in</th><th>Clock out</th><th>Overtime</th><th></th></tr>
   <tr>
    <td>{{ $record?->time_in ?: '—' }}</td>
    <td>{{ $record?->time_out ?: '—' }}</td>
    <td>{{ number_format((float) ($record?->overtime_hours ?? 0), 2) }} hours</td>
    <td>
     @if(!$record)
      <form method="POST" action="{{ route('attendance.clock-in') }}" class="inline">@csrf<button>Clock in</button></form>
     @elseif(!$record->time_out)
      <form method="POST" action="{{ route('attendance.clock-out') }}" class="inline">@csrf<button>Clock out</button></form>
     @else
      Complete
     @endif
    </td>
   </tr>
  </table>
 @endif
</div>
@endsection
