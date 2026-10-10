@extends('layouts.app')
@section('title','Audit log')
@section('content')
<div class="card"><h3>Audit log</h3>
 <table>
  <tr><th>When</th><th>User</th><th>Action</th><th>Target</th><th>Details</th></tr>
  @forelse($logs as $log)
  <tr><td>{{ $log->created_at->format('M d, Y H:i') }}</td><td>{{ $log->user?->username ?? 'system' }}</td><td>{{ $log->action }}</td>
   <td>{{ $log->target_type }}{{ $log->target_id ? ' #'.$log->target_id : '' }}</td><td>{{ $log->details }}</td></tr>
  @empty <tr><td colspan="5">No entries yet.</td></tr> @endforelse
 </table>
 {{ $logs->links() }}
</div>
@endsection
