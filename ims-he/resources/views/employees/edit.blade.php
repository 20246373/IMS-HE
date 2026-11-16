@extends('layouts.app')
@section('title','Edit employee')
@section('content')
<div class="card" style="max-width:520px">
 <h3>Edit {{ $employee->full_name }}</h3>
 <p class="muted-text">Current daily rate: <strong>@peso($employee->daily_rate)</strong>
  &middot; Account: {{ $employee->user?->username ?? 'not linked' }}</p>
 <x-form-note />
 <p class="muted-text">Name, date hired and status are fixed after the record is created and cannot be edited here.</p>
 <form method="POST" action="{{ route('employees.update', $employee->employee_id) }}">@csrf @method('PUT')
  <label>First name <span class="opt">(read-only)</span> <input value="{{ $employee->first_name }}" readonly></label>
  <label>Last name <span class="opt">(read-only)</span> <input value="{{ $employee->last_name }}" readonly></label>
  <label>Date hired <span class="opt">(read-only)</span> <input value="{{ $employee->date_hired?->format('M d, Y') ?? 'Not recorded' }}" readonly></label>
  <label>Status <span class="opt">(read-only)</span> <input value="{{ $employee->status }}" readonly></label>
  <label>Position <span class="req">*</span> <input name="position" value="{{ old('position', $employee->position) }}" maxlength="50" required></label>
  <label>Daily rate (&#8369;) <span class="req">*</span> <input type="number" step="0.01" min="0.01" name="daily_rate" value="{{ old('daily_rate', $employee->daily_rate) }}" required></label>
  @include('employees._ids', ['employee' => $employee])
  <p><button>Save changes</button> <a class="btn muted" href="{{ route('employees.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
