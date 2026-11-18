@extends('layouts.app')
@section('title', 'Employees')
@section('heading', 'Employees')
@section('page-actions')<a class="btn" href="{{ route('employees.create') }}">+ Add employee</a>@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('employees.index') }}" class="report-filter">
  <label>Search name <input type="search" name="q" value="{{ $search }}" placeholder="First or last name"></label>
  <button type="submit">Search</button>
  @if($search !== '')<a class="btn btn-alt" href="{{ route('employees.index') }}">Clear</a>@endif
 </form>
 <table>
  <thead><tr><th>Name</th><th>Position</th><th>Status</th><th>Daily rate</th><th>Email</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($employees as $employee)
   <tr>
    <td>{{ $employee->full_name }}</td>
    <td>{{ $employee->position }}</td>
    <td>{{ $employee->status }}</td>
    <td>{{ money($employee->daily_rate) }}</td>
    <td>{{ $employee->user?->email ?: '—' }}</td>
    <td>
     <a class="btn" href="{{ route('employees.edit', $employee) }}">Edit</a>
     @if($employee->status !== 'Resigned')
      <form method="POST" action="{{ route('employees.resign', $employee) }}" class="inline" onsubmit="return confirm('Mark this employee as resigned?')">
       @csrf
       <button class="danger">Resign</button>
      </form>
     @endif
    </td>
   </tr>
   @empty
   <tr><td colspan="6">No employees found.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $employees->links() }}
</div>
@endsection
