@extends('layouts.app')
@section('title','Employees')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Employees</h3>
  <a class="btn" href="{{ route('employees.create') }}">+ New employee</a>
 </div>
 <p class="muted-text">To link an employee to a login account, ask the Administrator to use Accounts &rarr; Edit (the "Linked employee" field).</p>
 <div style="overflow-x:auto">
 <table>
  <tr><th>Name</th><th>Position</th><th class="num">Daily rate</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>TIN</th><th>Date hired</th><th>Status</th><th>Account</th><th></th></tr>
  @forelse($employees as $e)
  <tr>
   <td>{{ $e->full_name }}</td>
   <td>{{ $e->position }}</td>
   <td class="num">@peso($e->daily_rate)</td>
   <td>{{ $e->sss_no ?? '-' }}</td>
   <td>{{ $e->philhealth_no ?? '-' }}</td>
   <td>{{ $e->pagibig_no ?? '-' }}</td>
   <td>{{ $e->tin_no ?? '-' }}</td>
   <td>{{ $e->date_hired?->format('M d, Y') ?? '-' }}</td>
   <td>{{ $e->status }}</td>
   <td>{{ $e->user?->label() ?? 'Not linked' }}</td>
   <td><a class="btn" href="{{ route('employees.edit', $e->employee_id) }}">Edit</a></td>
  </tr>
  @empty <tr><td colspan="11">No employees yet.</td></tr> @endforelse
 </table>
 </div>
 {{ $employees->links() }}
</div>
@endsection
