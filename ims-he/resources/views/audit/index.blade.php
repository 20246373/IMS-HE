@extends('layouts.app')
@section('title','Audit log')
@section('heading', 'Audit Log')
@section('page-actions')<a class="btn" href="{{ route('audit.export', request()->only(['user_id', 'about', 'action', 'start_date', 'end_date'])) }}">Download CSV</a>@endsection
@section('content')
<div class="card">
 <form method="GET" class="report-filter">
  <x-select label="Name" name="user_id" :required="false">
   <option value="">All names</option>
   @foreach($users as $u)<option value="{{ $u->user_id }}" @selected((string) request('user_id') === (string) $u->user_id)>{{ $u->displayName() }}</option>@endforeach
  </x-select>
  <x-select label="Type of user" name="about" :required="false">
   <option value="">All users</option>
   <option value="employees" @selected(request('about') === 'employees')>Employees</option>
   <option value="customers" @selected(request('about') === 'customers')>Customers</option>
  </x-select>
  <x-select label="Action" name="action" :required="false">
   <option value="">All actions</option>
   @foreach($actions as $value => $label)<option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>@endforeach
  </x-select>
  <x-field label="From" name="start_date" type="date" :value="request('start_date')" :required="false" />
  <x-field label="To" name="end_date" type="date" :value="request('end_date')" :required="false" />
  <div class="filter-buttons">
   <button>Filter</button>
   @if(request()->hasAny(['user_id', 'about', 'action', 'start_date', 'end_date']))
    <a class="btn btn-alt" href="{{ route('audit.index') }}">Clear</a>
   @endif
  </div>
 </form>
 <table>
  <tr><th>Date</th><th>Time</th><th>Name</th><th>Action</th></tr>
  @forelse($logs as $l)
  @php($at = \Illuminate\Support\Carbon::parse($l->logged_at))
  <tr>
   <td>{{ $at->format('M d, Y') }}</td>
   <td>{{ $at->format('h:i:s A') }}</td>
   <td>{{ $l->actorName() }}</td>
   <td>{{ $l->describe() }}</td>
  </tr>
  @empty
  <tr><td colspan="4">No entries.</td></tr>
  @endforelse
 </table>
 {{ $logs->links() }}
</div>
@endsection
