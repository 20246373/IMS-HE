@extends('layouts.app')
@section('title','New account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>New staff account</h3>
 <form method="POST" action="{{ route('accounts.store') }}">@csrf
  <label>Username <input name="username" value="{{ old('username') }}" required></label>
  <label>Full name <input name="name" value="{{ old('name') }}" required></label>
  <label>Email (optional) <input type="email" name="email" value="{{ old('email') }}"></label>
  <label>Role <select name="role" required>
   @foreach(\App\Models\User::STAFF_ROLES as $r)<option value="{{ $r }}" @selected(old('role')===$r)>{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach
  </select></label>
  <label>Password (min 8) <input type="password" name="password" required></label>
  <label>Confirm password <input type="password" name="password_confirmation" required></label>
  <p><button>Create account</button> <a class="btn muted" href="{{ route('accounts.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
