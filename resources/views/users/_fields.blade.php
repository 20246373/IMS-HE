<label>Email <input type="email" name="email" value="{{ old('email', $user?->email) }}" maxlength="100"></label>
<label>Username (optional if there is an email) <input name="username" value="{{ old('username', $user?->username) }}" maxlength="50"></label>
<label>Role
 <select name="role" required>
  @foreach($roles as $r)
   <option value="{{ $r }}" @selected(old('role', $user?->role) === $r)>{{ $r === 'Pending' ? 'Pending (no access)' : $r }}</option>
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
