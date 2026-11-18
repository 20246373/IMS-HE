@extends('layouts.app')
@section('title', $role->exists ? 'Edit role' : 'Add role')
@section('heading'){{ $role->exists ? $role->role_name : 'Add Role' }}@endsection
@section('content')
@php($checked = old('permissions', $granted))
<form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
 @csrf
 @if($role->exists) @method('PUT') @endif

 <div class="card">
  <h3>Role details</h3>
  <x-required-note />
  <div class="form-grid">
   <x-field label="Role name" name="role_name" :value="$role->role_name" maxlength="50" />
   <x-field label="Description" name="description" :value="$role->description" maxlength="255" :required="false" />
  </div>
 </div>

 <div class="card">
  <h3>Access</h3>
  <p class="text-muted" style="margin-top:0">Choose what people with this role can open. Changes apply the next time they load a page.</p>
  @error('permissions')<div class="field-error">{{ $message }}</div>@enderror
  <div class="permission-groups">
   @foreach($groups as $group => $permissions)
    <fieldset class="permission-group">
     <legend>{{ $group }}</legend>
     @foreach($permissions as $key => $label)
      <label class="check">
       <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $checked, true))>
       {{ $label }}
      </label>
     @endforeach
    </fieldset>
   @endforeach
  </div>
 </div>

 <div class="actions">
  <button>{{ $role->exists ? 'Save changes' : 'Add role' }}</button>
  <a class="btn btn-alt" href="{{ route('roles.index') }}">Cancel</a>
 </div>
</form>
@endsection
