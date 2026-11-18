@extends('layouts.app')
@section('title', 'Roles')
@section('heading', 'Roles')
@section('page-actions')<a class="btn" href="{{ route('roles.create') }}">+ Add role</a>@endsection
@section('content')
<div class="card">
 <table>
  <tr><th>Role</th><th>Description</th><th>Access</th><th>Accounts</th><th>Actions</th></tr>
  @foreach($roles as $role)
   @php($granted = $permissions[$role->role_id] ?? [])
   <tr>
    <td><strong>{{ $role->role_name }}</strong>@if($role->is_system)<span class="cell-sub">System role</span>@endif</td>
    <td>{{ $role->description ?: '—' }}</td>
    <td>
     {{-- Opens the pop-up below that lists this role's access --}}
     <a href="#access-{{ $role->role_id }}" class="access-link" onclick="document.getElementById('access-{{ $role->role_id }}').showModal(); return false;">
      @if($role->is_system)
       Accounts, roles and audit log
      @else
       {{ count($granted) }} {{ count($granted) === 1 ? 'permission' : 'permissions' }}
      @endif
     </a>
    </td>
    <td>{{ $counts[$role->role_name] ?? 0 }}</td>
    <td>
     @if($role->is_system)
      <span class="text-muted">Fixed</span>
     @else
      <div class="actions">
       <a class="btn" href="{{ route('roles.edit', $role) }}">Edit</a>
       <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ $role->role_name }} role?')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
      </div>
     @endif
    </td>
   </tr>
  @endforeach
 </table>
</div>

{{-- One pop-up per role: what that role can open, grouped by area. Closes with the button, Esc, or a click outside. --}}
@foreach($roles as $role)
 @php($granted = $permissions[$role->role_id] ?? [])
 <dialog id="access-{{ $role->role_id }}" class="access-dialog" onclick="if (event.target === this) this.close()">
  <h3 tabindex="-1" autofocus>{{ $role->role_name }}</h3>
  <p>This role has access to the following:</p>
  <div class="access-groups">
  @if($role->is_system)
   <div><h4>Administration</h4><ul class="access-list"><li>User accounts</li><li>Roles</li><li>Audit log</li></ul></div>
  @endif
  @foreach($groups as $group => $groupPermissions)
   @php($inGroup = array_intersect_key($groupPermissions, array_flip($granted)))
   @if($inGroup)
    <div><h4>{{ $group }}</h4>
    <ul class="access-list">@foreach($inGroup as $label)<li>{{ $label }}</li>@endforeach</ul></div>
   @endif
  @endforeach
  </div>
  @if(! $role->is_system && $granted === [])
   <p class="text-muted">Nothing yet. Edit the role to give it access.</p>
  @endif
  <div class="actions">
   @unless($role->is_system)<a class="btn" href="{{ route('roles.edit', $role) }}">Edit access</a>@endunless
   <button type="button" class="btn-alt" onclick="this.closest('dialog').close()">Close</button>
  </div>
 </dialog>
@endforeach
@endsection
