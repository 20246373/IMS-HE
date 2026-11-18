{{-- Supplier fields for the add / edit pop-ups. Old input and errors only show in the pop-up that was submitted ($reopened). --}}
@php($value = fn (string $field) => $reopened ? old($field) : $supplier?->{$field})
<div class="form-grid single">
 @if($withName)
  <div class="field">
   <label for="{{ $prefix }}-name" class="required">Supplier name</label>
   <input id="{{ $prefix }}-name" name="supplier_name" value="{{ $value('supplier_name') }}" maxlength="100" required>
   @if($reopened)@error('supplier_name')<div class="field-error">{{ $message }}</div>@enderror @endif
  </div>
 @endif
 <div class="field">
  <label for="{{ $prefix }}-contact" class="required">Contact number</label>
  <input id="{{ $prefix }}-contact" name="contact_number" value="{{ $value('contact_number') }}" maxlength="14" required>
  @if($reopened)@error('contact_number')<div class="field-error">{{ $message }}</div>@enderror @endif
 </div>
 <div class="field">
  <label for="{{ $prefix }}-address" class="required">Address</label>
  <input id="{{ $prefix }}-address" name="address" value="{{ $value('address') }}" maxlength="100" required>
  @if($reopened)@error('address')<div class="field-error">{{ $message }}</div>@enderror @endif
 </div>
</div>
