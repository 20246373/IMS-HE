@extends('layouts.app')
@section('title', 'Add employee')
@section('heading', 'Add Employee')
@section('content')
<div class="card">
 <x-required-note />
 <form method="POST" action="{{ route('employees.store') }}">
  @csrf
  @include('employees._form')
  <div class="actions">
   <button type="submit">Add employee</button>
   <a class="btn btn-alt" href="{{ route('employees.index') }}">Cancel</a>
  </div>
 </form>
</div>
@endsection
