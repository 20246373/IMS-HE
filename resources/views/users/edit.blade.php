@extends('layouts.app')
@section('title','Edit account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit {{ $user->username }}</h3>
 <form method="POST" action="{{ route('users.update', $user) }}">@csrf @method('PUT')
  @include('users._fields')
  <label>New password (leave blank to keep current) <input type="password" name="password"></label>
  <label>Confirm password <input type="password" name="password_confirmation"></label>
  <p><button>Save</button> <a class="btn danger" href="{{ route('users.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
