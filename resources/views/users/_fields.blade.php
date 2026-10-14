<label>Username <input name="username" value="{{ old('username', $user?->username) }}" required maxlength="50"></label>
<label>Role
 <select name="role" required>
  @foreach(\App\Models\User::ROLES as $r)
   <option value="{{ $r }}" @selected(old('role', $user?->role) === $r)>{{ $r }}</option>
  @endforeach
 </select>
</label>
<label>Linked employee (optional)
 <select name="employee_id">
  <option value="">-- none --</option>
  @foreach($employees as $e)
   <option value="{{ $e->employee_id }}" @selected((string) old('employee_id', $user?->employee_id) === (string) $e->employee_id)>{{ $e->full_name }} - {{ $e->position }}</option>
  @endforeach
 </select>
</label>
