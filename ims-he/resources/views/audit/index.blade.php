@extends('layouts.app')
@section('title','Audit log')
@section('heading', 'Audit Log')
@section('content')
<div class="card">
 <form method="GET" class="report-filter">
  <x-select label="Name" name="user_id" :required="false">
   <option value="">All names</option>
   @foreach($users as $u)<option value="{{ $u->user_id }}" @selected((string) request('user_id') === (string) $u->user_id)>{{ $u->displayName() }}</option>@endforeach
  </x-select>
  <x-select label="Action" name="action" :required="false">
   <option value="">All actions</option>
   @foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach
  </x-select>
  <x-field label="From" name="start_date" type="date" :value="request('start_date')" :required="false" />
  <x-field label="To" name="end_date" type="date" :value="request('end_date')" :required="false" />
  <div class="filter-buttons">
   <button>Filter</button>
   @if(request()->hasAny(['user_id', 'action', 'start_date', 'end_date']))
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
   <td>{{ $l->user?->displayName() ?? 'System' }}</td>
   <td>{{ $l->action }}</td>
  </tr>
  @empty
  <tr><td colspan="4">No entries.</td></tr>
  @endforelse
 </table>
 {{ $logs->links() }}
</div>
@endsection
