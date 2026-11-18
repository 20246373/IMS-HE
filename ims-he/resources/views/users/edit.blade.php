@extends('layouts.app')
@section('title','Edit account')
@section('heading'){{ $user->displayName() }}@endsection
@section('content')
<div class="split even">
 <div class="card">
  <h3>Account details</h3>
  <form method="POST" action="{{ route('users.update', $user) }}" class="plain-labels">@csrf @method('PUT')
   @include('users._fields')
   <div class="actions"><button>Save</button> <a class="btn danger" href="{{ route('users.index') }}">Cancel</a></div>
  </form>
 </div>
 <div class="card">
  <h3>Reset password</h3>
  <form method="POST" action="{{ route('users.password', $user) }}" class="plain-labels">@csrf
   <div class="form-grid single">
    <x-password label="New password" hint="Must be at least 8 characters." minlength="8" maxlength="72" autocomplete="new-password" />
    <x-password label="Confirm password" name="password_confirmation" autocomplete="new-password" />
   </div>
   <div class="actions"><button>Reset password</button></div>
  </form>
 </div>
</div>
@endsection
