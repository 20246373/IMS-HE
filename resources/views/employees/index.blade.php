@extends('layouts.app')
@section('title','Employees')
@section('content')
<div class="card">
 <h3>Employee Management</h3>
 <p><a href="{{ route('employees.create') }}" class="btn">Add Employee</a></p>
 <table>
  <thead><tr><th>Name</th><th>Position</th><th>Daily Rate</th><th>Status</th><th>Linked User</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($employees as $employee)
    <tr>
     <td>{{ $employee->last_name }}, {{ $employee->first_name }}</td>
     <td>{{ $employee->position }}</td>
     <td>{{ number_format($employee->daily_rate, 2) }}</td>
     <td>{{ $employee->status }}</td>
     <td>{{ $employee->user?->username ?? '-' }}</td>
     <td>
      <a href="{{ route('employees.edit', $employee) }}" class="btn">Edit</a>
     </td>
    </tr>
   @endforeach
  </tbody>
 </table>
 {{ $employees->links() }}
</div>
@endsection
