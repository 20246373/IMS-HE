@extends('layouts.app')
@section('title', 'Edit employee')
@section('content')
<div class="card">
 <h2>Edit employee</h2>
 <x-required-note />
 <form method="POST" action="{{ route('employees.update', $employee) }}">
  @csrf
  @method('PUT')
  @include('employees._form')
  <button type="submit">Save changes</button>
  <a class="btn btn-alt" href="{{ route('employees.index') }}">Cancel</a>
 </form>
</div>
@endsection
