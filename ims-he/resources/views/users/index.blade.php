@extends('layouts.app')
@section('title','Accounts')
@section('heading', 'User Accounts')
@section('content')
@if($pending->isNotEmpty())
<div class="card" id="unassigned">
 <h3>Unassigned accounts</h3>
 <p><small>Choose a role to give them access, or delete the account.</small></p>
 <table>
  <tr><th>Email</th><th>Signed Up</th><th>Role</th><th>Employee Record</th><th>Actions</th></tr>
  @foreach($pending as $p)
  <tr>
   <td>{{ $p->email }}</td>
   <td>{{ $p->created_at?->format('M d, Y') }}</td>
   <td>
    <select name="role" form="role-{{ $p->user_id }}" required>
     @foreach($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
    </select>
   </td>
   <td>
    <select name="employee_id" form="role-{{ $p->user_id }}">
     <option value="">-- none --</option>
     @foreach($employees as $e)<option value="{{ $e->employee_id }}">{{ $e->full_name }}</option>@endforeach
    </select>
   </td>
   <td>
    <div class="actions">
     <form id="role-{{ $p->user_id }}" method="POST" action="{{ route('users.role', $p) }}">@csrf<button>Approve</button></form>
     <form method="POST" action="{{ route('users.destroy', $p) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
    </div>
   </td>
  </tr>
  @endforeach
 </table>
</div>
@endif

<div class="card">
 <table>
  <tr><th>Email</th><th>Employee</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
  @foreach($users as $u)
  <tr>
   <td>{{ $u->email }}</td>
   <td>{{ $u->employee?->full_name ?? '-' }}</td>
   <td>{{ $u->role }}</td>
   <td class="{{ $u->isLocked() ? 'low' : '' }}">{{ $u->status }}</td>
   <td>
    @if($u->last_login)
     {{ $u->last_login->format('M d, Y') }}<span class="cell-sub">{{ $u->last_login->format('h:i A') }}</span>
    @else
     <span class="text-muted">User has not logged in</span>
    @endif
   </td>
   <td>
    <div class="actions">
     <a class="btn" href="{{ route('users.edit', $u) }}">Edit</a>
     @if($u->isLocked())
      <form method="POST" action="{{ route('users.unlock', $u) }}">@csrf<button>Unlock</button></form>
     @elseif(!$u->is(auth()->user()))
      <form method="POST" action="{{ route('users.lock', $u) }}">@csrf<button class="danger">Lock</button></form>
     @endif
     @if(!$u->is(auth()->user()))
      <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
     @endif
    </div>
   </td>
  </tr>
  @endforeach
 </table>
</div>
@endsection
