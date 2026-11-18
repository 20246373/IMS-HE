@extends('layouts.app')
@section('title','User Accounts')
@section('heading', 'User Accounts')

@push('head')
<style>
  .badge-status {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
  }
  .badge-active { background: #e6f4ea; color: #1e6b34; }
  .badge-locked { background: #fdecea; color: #b42318; }
  .badge-pending { background: #fff5d8; color: #785b00; }

  .user-table select {
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    font-size: 0.85rem;
  }

  .card-header-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.85rem;
  }
  .card-header-title h3 { margin: 0; color: #1f3a5f; }
</style>
@endpush

@section('content')

{{-- Unassigned / Pending Accounts Section --}}
@if($pending->isNotEmpty())
<div class="card" id="unassigned" style="border-left: 5px solid #f2b705; margin-bottom: 1.5rem;">
 <div class="card-header-title">
  <h3>Pending Account Approvals</h3>
  <span class="badge-status badge-pending">{{ $pending->count() }} Pending</span>
 </div>
 <p style="margin-top: 0; color: #64748b; font-size: 0.9rem;">Assign an employee record and role to grant workspace access.</p>
 
 <table class="user-table">
  <thead>
   <tr>
    <th>Email</th>
    <th>Signed Up</th>
    <th>Assign Role</th>
    <th>Employee Record</th>
    <th>Actions</th>
   </tr>
  </thead>
  <tbody>
   @foreach($pending as $p)
   <tr>
    <td><strong>{{ $p->email }}</strong></td>
    <td>{{ $p->created_at?->format('M d, Y') }}</td>
    <td>
     <select name="role" form="role-{{ $p->user_id }}" required>
      @foreach($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
     </select>
    </td>
    <td>
     <select name="employee_id" form="role-{{ $p->user_id }}">
      <option value="">-- None --</option>
      @foreach($employees as $e)<option value="{{ $e->employee_id }}">{{ $e->full_name }}</option>@endforeach
     </select>
    </td>
    <td>
     <div class="actions">
      <form id="role-{{ $p->user_id }}" method="POST" action="{{ route('users.role', $p) }}">@csrf<button class="btn" style="background: #1e6b34;">Approve</button></form>
      <form method="POST" action="{{ route('users.destroy', $p) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="btn danger">Delete</button></form>
     </div>
    </td>
   </tr>
   @endforeach
  </tbody>
 </table>
</div>
@endif

{{-- Main User Accounts Table --}}
<div class="card">
 <div class="card-header-title">
  <h3>Active System Users</h3>
 </div>
 
 <table class="user-table">
  <thead>
   <tr>
    <th>Email</th>
    <th>Employee Name</th>
    <th>Assigned Role</th>
    <th>Status</th>
    <th>Last Activity</th>
    <th>Actions</th>
   </tr>
  </thead>
  <tbody>
   @foreach($users as $u)
   <tr>
    <td><strong>{{ $u->email }}</strong></td>
    <td>{{ $u->employee?->full_name ?? '-' }}</td>
    <td><span style="font-weight: 600; color: #1f3a5f;">{{ $u->role }}</span></td>
    <td>
     @if($u->isLocked())
      <span class="badge-status badge-locked">Locked</span>
     @else
      <span class="badge-status badge-active">Active</span>
     @endif
    </td>
    <td>
     @if($u->last_login)
      <div>{{ $u->last_login->format('M d, Y') }}</div>
      <span class="cell-sub">{{ $u->last_login->format('h:i A') }}</span>
     @else
      <span class="text-muted">Never</span>
     @endif
    </td>
    <td>
     <div class="actions">
      <a class="btn" href="{{ route('users.edit', $u) }}">Edit</a>
      @if($u->isLocked())
       <form method="POST" action="{{ route('users.unlock', $u) }}">@csrf<button class="btn" style="background: #1e6b34;">Unlock</button></form>
      @elseif(!$u->is(auth()->user()))
       <form method="POST" action="{{ route('users.lock', $u) }}">@csrf<button class="btn danger">Lock</button></form>
      @endif
      @if(!$u->is(auth()->user()))
       <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="btn danger">Delete</button></form>
      @endif
     </div>
    </td>
   </tr>
   @endforeach
  </tbody>
 </table>
</div>
@endsection