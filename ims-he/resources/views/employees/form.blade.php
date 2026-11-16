@extends('layouts.app')
@section('title', $employee->exists ? 'Edit employee' : 'Add employee')
@section('content')
<div class="card" style="max-width:640px">
 <h2>{{ $employee->exists ? 'Edit employee' : 'Add employee' }}</h2>
 <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
  @csrf
  @if($employee->exists) @method('PUT') @endif
  <div class="form-grid">
   <div class="field"><label class="required">First name</label><input name="first_name" value="{{ old('first_name', $employee->first_name) }}" maxlength="50" required></div>
   <div class="field"><label class="required">Last name</label><input name="last_name" value="{{ old('last_name', $employee->last_name) }}" maxlength="50" required></div>
   <div class="field"><label class="required">Position</label><input name="position" value="{{ old('position', $employee->position) }}" maxlength="50" required></div>
   <div class="field"><label class="required">Daily rate (&#8369;)</label><input type="number" name="daily_rate" step="0.01" min="0" value="{{ old('daily_rate', $employee->daily_rate) }}" required></div>
   <div class="field"><label class="optional">SSS no.</label><input name="sss_no" value="{{ old('sss_no', $employee->sss_no) }}" maxlength="20"></div>
   <div class="field"><label class="optional">PhilHealth no.</label><input name="philhealth_no" value="{{ old('philhealth_no', $employee->philhealth_no) }}" maxlength="20"></div>
   <div class="field"><label class="optional">Pag-IBIG no.</label><input name="pagibig_no" value="{{ old('pagibig_no', $employee->pagibig_no) }}" maxlength="20"></div>
   <div class="field"><label class="optional">BIR TIN</label><input name="tin_no" value="{{ old('tin_no', $employee->tin_no) }}" maxlength="20"></div>
   <div class="field"><label class="optional">Date hired</label><input type="date" name="date_hired" value="{{ old('date_hired', $employee->date_hired?->toDateString()) }}" max="{{ today()->toDateString() }}"></div>
   <div class="field"><label class="required">Status</label>
    <select name="status" required>
     <option value="Active" @selected(old('status', $employee->status ?? 'Active') === 'Active')>Active</option>
     <option value="Resigned" @selected(old('status', $employee->status) === 'Resigned')>Resigned</option>
    </select>
   </div>
  </div>
  <p>
   <button type="submit">{{ $employee->exists ? 'Save changes' : 'Add employee' }}</button>
   <a class="btn" href="{{ route('employees.index') }}">Cancel</a>
  </p>
 </form>
</div>
@endsection
