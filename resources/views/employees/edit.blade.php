@extends('layouts.app')
@section('title','Edit Employee')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Edit {{ $employee->last_name }}, {{ $employee->first_name }}</h3>
 <form method="POST" action="{{ route('employees.update', $employee) }}">@csrf @method('PUT')
  <label>First Name <input type="text" name="first_name" value="{{ $employee->first_name }}" required></label>
  <label>Last Name <input type="text" name="last_name" value="{{ $employee->last_name }}" required></label>
  <label>Position <input type="text" name="position" value="{{ $employee->position }}" required></label>
  <label>Daily Rate <input type="number" step="0.01" name="daily_rate" value="{{ $employee->daily_rate }}" required></label>
  <label>SSS No <input type="text" name="sss_no" value="{{ $employee->sss_no }}"></label>
  <label>PhilHealth No <input type="text" name="philhealth_no" value="{{ $employee->philhealth_no }}"></label>
  <label>Pag-IBIG No <input type="text" name="pagibig_no" value="{{ $employee->pagibig_no }}"></label>
  <label>TIN No <input type="text" name="tin_no" value="{{ $employee->tin_no }}"></label>
  <label>Date Hired <input type="date" name="date_hired" value="{{ $employee->date_hired }}"></label>
  <label>Status <select name="status" required><option value="Active" {{ $employee->status === 'Active' ? 'selected' : '' }}>Active</option><option value="Inactive" {{ $employee->status === 'Inactive' ? 'selected' : '' }}>Inactive</option></select></label>
  <p><button>Save</button> <a href="{{ route('employees.index') }}" class="btn btn-alt">Cancel</a></p>
 </form>
</div>
@endsection
