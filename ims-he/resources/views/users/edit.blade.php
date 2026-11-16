@extends('layouts.app')
@section('title','Edit account')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit {{ $user->label() }}</h3>
 <form method="POST" action="{{ route('users.update', $user) }}">@csrf @method('PUT')
  <x-required-note />
  @include('users._fields', ['kit' => true])
  <p><button>Save</button> <a class="btn danger" href="{{ route('users.index') }}">Cancel</a></p>
 </form>
</div>
<div class="card" style="max-width:480px">
 <h3>Reset password</h3>
 <form method="POST" action="{{ route('users.password', $user) }}">@csrf
  <x-required-note />
  <div class="form-grid">
   <x-field label="New password (min 8)" name="password" type="password" />
   <x-field label="Confirm password" name="password_confirmation" type="password" />
  </div>
  <p><button>Reset password</button></p>
 </form>
</div>
@endsection
