@extends('layouts.app')
@section('title','Edit account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit {{ $user->username }} @if($user->is_locked)<span class="badge red">Locked</span>@endif</h3>
 <form method="POST" action="{{ route('accounts.update',$user) }}">@csrf @method('PUT')
  <label>Full name <input name="name" value="{{ old('name',$user->name) }}" required></label>
  <label>Email <input type="email" name="email" value="{{ old('email',$user->email) }}"></label>
  @if($user->isStaff())
   @if($user->id === auth()->id())
    <p><small>Role: {{ str_replace('_',' ',$user->role) }} (you are the only Admin.)</small></p>
   @else
   <label>Role <select name="role">
    @foreach(\App\Models\User::STAFF_ROLES as $r)<option value="{{ $r }}" @selected(old('role',$user->role)===$r)>{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach
   </select></label>
   @endif
  @endif
  <p><button>Save</button> <a class="btn muted" href="{{ route('accounts.index') }}">Back</a></p>
 </form>
</div>
<div class="card" style="max-width:480px">
 <h3>Reset password</h3>
 <form method="POST" action="{{ route('accounts.password',$user) }}">@csrf
  <label>New password (min 8) <input type="password" name="password" required></label>
  <label>Confirm <input type="password" name="password_confirmation" required></label>
  <p><button>Reset password</button></p>
 </form>
</div>
@endsection
