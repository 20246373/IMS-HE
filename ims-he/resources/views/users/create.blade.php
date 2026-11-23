@extends('layouts.app')
@section('title','Add account')
@section('heading', 'Add Account')
@section('content')
<div class="card">
 <form method="POST" action="{{ route('users.store') }}">@csrf
  <div class="form-grid">
   <x-field label="First name" name="first_name" maxlength="50" autocomplete="off" />
   <x-field label="Last name" name="last_name" maxlength="50" autocomplete="off" />
   <x-field label="Email" name="email" type="email" maxlength="100" autocomplete="off" />
   <x-select label="Role" name="role">
    @foreach($roles as $r)
     <option value="{{ $r }}" @selected(old('role') === $r)>{{ $r }}</option>
    @endforeach
   </x-select>
  </div>
  <div class="actions"><button>Create account</button> <a class="btn btn-alt" href="{{ route('users.index') }}">Cancel</a></div>
 </form>
</div>
@endsection
