@extends('layouts.app')
@section('title','New account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>New account</h3>
 <form method="POST" action="{{ route('users.store') }}">@csrf
  @include('users._fields', ['user' => null])
  <label>Password (min 8) <input type="password" name="password" required></label>
  <label>Confirm password <input type="password" name="password_confirmation" required></label>
  <p><button>Create</button> <a class="btn danger" href="{{ route('users.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
