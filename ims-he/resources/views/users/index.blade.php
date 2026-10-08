@extends('layouts.app')
@section('title','Accounts')
@section('content')
<div class="card">
 <p><a class="btn" href="{{ route('users.create') }}">+ New account</a></p>
 <table>
  <tr><th>Username</th><th>Employee</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr>
  @foreach($users as $u)
  <tr>
   <td>{{ $u->username }}</td>
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
