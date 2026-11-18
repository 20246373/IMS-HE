<div class="form-grid">
 <x-field label="Email" name="email" type="email" :value="$user->email" maxlength="100" autocomplete="off" />
 @if($user->is(auth()->user()) && $user->hasRole(\App\Models\User::ADMINISTRATOR))
  <input type="hidden" name="role" value="{{ $user->role }}">
  <x-value label="Role" hint="You cannot change your own role.">{{ $user->role }}</x-value>
 @else
  <x-select label="Role" name="role">
   @foreach($roles as $r)
    <option value="{{ $r }}" @selected(old('role', $user->role) === $r)>{{ $r }}</option>
   @endforeach
  </x-select>
 @endif
 <x-select label="Status" name="status">
   @foreach($statuses as $status)
    <option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ $status }}</option>
   @endforeach
 </x-select>
 <x-select label="Linked employee" name="employee_id">
  <option value="" disabled @selected(! old('employee_id', $user->employee_id))>Choose an employee</option>
  @foreach($employees as $e)
   <option value="{{ $e->employee_id }}" @selected((string) old('employee_id', $user->employee_id) === (string) $e->employee_id)>{{ $e->full_name }} - {{ $e->position }}</option>
  @endforeach
 </x-select>
</div>
