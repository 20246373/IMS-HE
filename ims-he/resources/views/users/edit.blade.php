@extends('layouts.app')
@section('title','Edit account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit {{ $user->username }}</h3>
 <form method="POST" action="{{ route('users.update', $user) }}">@csrf @method('PUT')
  @include('users._fields')
  <p><button>Save</button> <a class="btn danger" href="{{ route('users.index') }}">Cancel</a></p>
 </form>
</div>
<div class="card" style="max-width:480px">
 <h3>Reset password</h3>
 <form method="POST" action="{{ route('users.password', $user) }}">@csrf
  <label>New password (min 8) <input type="password" name="password" required></label>
  <label>Confirm password <input type="password" name="password_confirmation" required></label>
  <p><button>Reset password</button></p>
 </form>
</div>
@endsection
