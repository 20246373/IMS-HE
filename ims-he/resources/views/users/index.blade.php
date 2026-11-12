@extends('layouts.app')
@section('title','Accounts')
@section('content')
@if($pending->isNotEmpty())
<div class="card">
 <h3>Waiting for a role ({{ $pending->count() }})</h3>
 <p><small>These employees signed up themselves. Choose a role to give them access, or delete the account if you do not know them.</small></p>
 <table>
  <tr><th>Email</th><th>Signed up</th><th>Role</th><th>Employee record</th><th></th></tr>
  @foreach($pending as $p)
  <tr>
   <td>{{ $p->label() }}</td>
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
    <form id="role-{{ $p->user_id }}" method="POST" action="{{ route('users.role', $p) }}" class="inline">@csrf<button>Give role</button></form>
    <form method="POST" action="{{ route('users.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
   </td>
  </tr>
  @endforeach
 </table>
</div>
@endif

<div class="card">
 <p><a class="btn" href="{{ route('users.create') }}">+ New account</a></p>
 <table>
  <tr><th>Account</th><th>Employee</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr>
  @foreach($users as $u)
  <tr>
   <td>{{ $u->label() }}@if($u->username && $u->email)<br><small>{{ $u->email }}</small>@endif</td>
   <td>{{ $u->employee?->full_name ?? '-' }}</td>
   <td>{{ $u->role }}</td>
   <td class="{{ $u->isLocked() ? 'low' : '' }}">{{ $u->status }}</td>
   <td>{{ $u->last_login?->format('M d, Y H:i') ?? 'never' }}</td>
   <td>
    <a class="btn" href="{{ route('users.edit', $u) }}">Edit</a>
    @if($u->isLocked())
     <form method="POST" action="{{ route('users.unlock', $u) }}" class="inline">@csrf<button>Unlock</button></form>
    @elseif(!$u->is(auth()->user()))
     <form method="POST" action="{{ route('users.lock', $u) }}" class="inline">@csrf<button class="danger">Lock</button></form>
    @endif
    @if(!$u->is(auth()->user()))
     <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
    @endif
   </td>
  </tr>
  @endforeach
 </table>
</div>
@endsection
