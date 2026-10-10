@extends('layouts.app')
@section('title','Accounts')
@section('content')
<div class="card">
 <div class="row" style="justify-content:space-between">
  <h3 style="margin:0">Accounts</h3>
  <a class="btn" href="{{ route('accounts.create') }}">+ New staff account</a>
 </div>
 <form method="GET" class="row">
  <input name="q" placeholder="Search username or name" value="{{ request('q') }}">
  <select name="role"><option value="">All roles</option>
   @foreach(array_merge(\App\Models\User::STAFF_ROLES, [\App\Models\User::CUSTOMER]) as $r)<option value="{{ $r }}" @selected(request('role')===$r)>{{ str_replace('_',' ',$r) }}</option>@endforeach
  </select>
  <button>Filter</button>
 </form>
 <table>
  <tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr>
  @foreach($users as $user)
  <tr>
   <td>{{ $user->username }}</td><td>{{ $user->name }}</td><td>{{ str_replace('_',' ',$user->role) }}</td>
   <td>@if($user->is_locked)<span class="badge red">Locked</span>@else<span class="badge green">Active</span>@endif</td>
   <td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
   <td class="row">
    <a class="btn" href="{{ route('accounts.edit',$user) }}">Edit</a>
    @if($user->id !== auth()->id())
    <form method="POST" action="{{ route('accounts.lock',$user) }}" class="inline">@csrf @method('PATCH')<button class="muted">{{ $user->is_locked ? 'Unlock' : 'Lock' }}</button></form>
    <form method="POST" action="{{ route('accounts.destroy',$user) }}" class="inline" onsubmit="return confirm('Delete account {{ $user->username }}?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
    @endif
   </td>
  </tr>
  @endforeach
 </table>
 {{ $users->links() }}
</div>
@endsection
