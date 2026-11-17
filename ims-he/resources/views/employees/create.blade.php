@extends('layouts.app')
@section('title', 'Add employee')
@section('content')
<div class="card">
 <h2>Add employee</h2>
 <x-required-note />
 <form method="POST" action="{{ route('employees.store') }}">
  @csrf
  @include('employees._form')
  <button type="submit">Add employee</button>
  <a class="btn btn-alt" href="{{ route('employees.index') }}">Cancel</a>
 </form>
</div>
@endsection
