@extends('layouts.app')
@section('title','Audit log')
@section('content')
<div class="card">
 <form method="GET" class="form-grid">
  <x-select label="User" name="user_id" :required="false">
   <option value="">All users</option>
   @foreach($users as $u)<option value="{{ $u->user_id }}" @selected((string) request('user_id') === (string) $u->user_id)>{{ $u->label() }}</option>@endforeach
  </x-select>
  <x-select label="Action" name="action" :required="false">
   <option value="">All actions</option>
   @foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach
  </x-select>
  <x-field label="Start date" name="start_date" type="date" :value="request('start_date')" :required="false" />
  <x-field label="End date" name="end_date" type="date" :value="request('end_date')" :required="false" />
  <div class="field"><label class="optional">&nbsp;</label>
  <button>Filter</button>
  </div>
 </form>
 <table>
  <tr><th>When</th><th>User</th><th>Action</th><th>Table</th><th>Record</th></tr>
  @forelse($logs as $l)
  <tr>
   <td>{{ \Illuminate\Support\Carbon::parse($l->logged_at)->format('M d, Y H:i:s') }}</td>
   <td>{{ $l->user?->username ?? '-' }}</td>
   <td>{{ $l->action }}</td><td>{{ $l->table_name }}</td><td>{{ $l->record_id }}</td>
  </tr>
  @empty
  <tr><td colspan="5">No entries.</td></tr>
  @endforelse
 </table>
 {{ $logs->links() }}
</div>
@endsection
