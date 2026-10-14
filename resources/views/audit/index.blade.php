@extends('layouts.app')
@section('title','Audit log')
@section('content')
<div class="card">
 <form method="GET">
  <select name="user_id"><option value="">All users</option>
   @foreach($users as $u)<option value="{{ $u->user_id }}" @selected((string) request('user_id') === (string) $u->user_id)>{{ $u->username }}</option>@endforeach
  </select>
  <select name="table_name"><option value="">All tables</option>
   @foreach($tables as $t)<option value="{{ $t }}" @selected(request('table_name') === $t)>{{ $t }}</option>@endforeach
  </select>
  <button>Filter</button>
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
