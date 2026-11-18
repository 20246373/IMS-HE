<div class="form-grid">
 @if($employee->exists)
  {{-- Name and hire date are fixed once the employee exists (shown as plain text). --}}
  <x-field label="First name" name="first_name" value="{{ $employee->first_name }}" maxlength="50" readonly />
  <x-field label="Last name" name="last_name" value="{{ $employee->last_name }}" maxlength="50" readonly />
 @else
  <x-field label="First name" name="first_name" maxlength="50" />
  <x-field label="Last name" name="last_name" maxlength="50" />
 @endif
 <x-field label="Email" name="email" type="email" :value="$employee->user?->email" maxlength="100" autocomplete="off" />
 <x-field label="Position" name="position" value="{{ $employee->position }}" maxlength="50" />
 <x-field label="Daily rate" name="daily_rate" value="{{ $employee->exists ? number_format((float) $employee->daily_rate, 2, '.', '') : null }}" prefix="₱" inputmode="decimal" />
 <x-field label="SSS number" name="sss_no" value="{{ $employee->sss_no }}" maxlength="20" />
 <x-field label="PhilHealth number" name="philhealth_no" value="{{ $employee->philhealth_no }}" maxlength="20" />
 <x-field label="Pag-IBIG number" name="pagibig_no" value="{{ $employee->pagibig_no }}" maxlength="20" />
 <x-field label="TIN" name="tin_no" value="{{ $employee->tin_no }}" maxlength="20" />
 @if($employee->exists)
  <x-field label="Date hired" name="date_hired" value="{{ \Illuminate\Support\Carbon::parse($employee->date_hired)->format('M d, Y') }}" readonly />
 @else
  <x-field label="Date hired" name="date_hired" type="date" />
 @endif
 <x-select label="Status" name="status">
  @foreach($statuses as $status)
   <option value="{{ $status }}" @selected(old('status', $employee->status) === $status)>{{ $status }}</option>
  @endforeach
 </x-select>
</div>
