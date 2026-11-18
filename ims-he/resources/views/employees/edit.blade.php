@extends('layouts.app')
@section('title', 'Edit employee')
@section('heading'){{ $employee->full_name }}@endsection
@section('content')
<div class="card">
 <x-required-note />
 <form method="POST" action="{{ route('employees.update', $employee) }}">
  @csrf
  @method('PUT')
  @include('employees._form')
  <div class="actions">
   <button type="submit">Save changes</button>
   <a class="btn btn-alt" href="{{ route('employees.index') }}">Cancel</a>
  </div>
 </form>
</div>
@endsection
