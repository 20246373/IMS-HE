@extends('layouts.app')
@section('title','New employee')
@section('content')
<div class="card" style="max-width:520px">
 <h3>New employee</h3>
 <x-form-note />
 <form method="POST" action="{{ route('employees.store') }}">@csrf
  <label>First name <span class="req">*</span> <input name="first_name" value="{{ old('first_name') }}" maxlength="50" required autofocus></label>
  <label>Last name <span class="req">*</span> <input name="last_name" value="{{ old('last_name') }}" maxlength="50" required></label>
  <label>Position <span class="req">*</span> <input name="position" value="{{ old('position') }}" maxlength="50" required></label>
  <label>Daily rate (&#8369;) <span class="req">*</span> <input type="number" step="0.01" min="0.01" name="daily_rate" value="{{ old('daily_rate') }}" required></label>
  @include('employees._ids', ['employee' => null])
  <label>Date hired <span class="opt">(optional)</span> <input type="date" name="date_hired" value="{{ old('date_hired') }}" max="{{ today()->toDateString() }}"></label>
  <label>Status <span class="req">*</span>
   <select name="status" required>
    @foreach([\App\Models\Employee::STATUS_ACTIVE, \App\Models\Employee::STATUS_RESIGNED] as $s)
     <option value="{{ $s }}" @selected(old('status', 'Active') === $s)>{{ $s }}</option>
    @endforeach
   </select></label>
  <p><button>Save employee</button> <a class="btn muted" href="{{ route('employees.index') }}">Cancel</a></p>
 </form>
</div>
@endsection
