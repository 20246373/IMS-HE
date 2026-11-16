@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Employees</h2>
  <a class="btn no-print" href="{{ route('employees.create') }}">Add employee</a>
 </div>
 <table>
  <thead><tr><th>Name</th><th>Position</th><th>Daily rate</th><th>Status</th><th>Hired</th><th></th></tr></thead>
  <tbody>
   @forelse($employees as $employee)
   <tr>
    <td>{{ $employee->full_name }}</td>
    <td>{{ $employee->position }}</td>
    <td>&#8369;{{ number_format((float) $employee->daily_rate, 2) }}</td>
    <td>{{ $employee->status }}</td>
    <td>{{ $employee->date_hired?->format('M d, Y') ?? '—' }}</td>
    <td><a class="btn" href="{{ route('employees.edit', $employee) }}">Edit</a></td>
   </tr>
   @empty
   <tr><td colspan="6">No employees yet.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $employees->links() }}
</div>
@endsection
