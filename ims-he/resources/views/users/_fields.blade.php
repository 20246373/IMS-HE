@php($kit = $kit ?? false)

@if($kit)
 <div class="form-grid">
  <x-field label="Email" name="email" type="email" :value="$user?->email" :required="false" maxlength="100" readonly />
  <x-field label="Username" name="username" :value="$user?->username" :required="false" maxlength="50" readonly />
  @if($user->is(auth()->user()) && $user->hasRole(\App\Models\User::ADMINISTRATOR))
   <x-field label="Role" name="role" :value="$user->role" readonly />
  @else
   <x-select label="Role" name="role">
    @foreach($roles as $r)
     <option value="{{ $r }}" @selected(old('role', $user?->role) === $r)>{{ $r }}</option>
    @endforeach
   </x-select>
  @endif
  <x-select label="Status" name="status">
    @foreach($statuses as $status)
     <option value="{{ $status }}" @selected(old('status', $user?->status) === $status)>{{ $status }}</option>
    @endforeach
  </x-select>
  <x-select label="Linked employee" name="employee_id" :required="false">
   <option value="">-- none --</option>
   @foreach($employees as $e)
    <option value="{{ $e->employee_id }}" @selected((string) old('employee_id', $user?->employee_id) === (string) $e->employee_id)>{{ $e->full_name }} - {{ $e->position }}</option>
   @endforeach
  </x-select>
 </div>
@else
 <label>Email <input type="email" name="email" value="{{ old('email', $user?->email) }}" maxlength="100"></label>
 <label>Username (optional if there is an email) <input name="username" value="{{ old('username', $user?->username) }}" maxlength="50"></label>
 <label>Role
  @if(in_array($user?->role, [\App\Models\User::ADMINISTRATOR, \App\Models\User::PENDING], true))
   <input type="hidden" name="role" value="{{ $user->role }}">
   <span>{{ $user->role === \App\Models\User::PENDING ? 'Pending (no access)' : $user->role }}</span>
  @else
   <select name="role" required>
    @foreach($roles as $r)
     <option value="{{ $r }}" @selected(old('role', $user?->role) === $r)>{{ $r }}</option>
    @endforeach
   </select>
  @endif
 </label>
 <label>Linked employee (optional)
  <select name="employee_id">
   <option value="">-- none --</option>
   @foreach($employees as $e)
    <option value="{{ $e->employee_id }}" @selected((string) old('employee_id', $user?->employee_id) === (string) $e->employee_id)>{{ $e->full_name }} - {{ $e->position }}</option>
   @endforeach
  </select>
 </label>
@endif
