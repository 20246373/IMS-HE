@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => true])

<div class="field">
 <label for="{{ $attributes->get('id', $name) }}" class="{{ $required ? 'required' : 'optional' }}">{{ $label }}</label>
 <input
  id="{{ $attributes->get('id', $name) }}"
  type="{{ $type }}"
  name="{{ $name }}"
  value="{{ old($name, $value) }}"
  @required($required)
  {{ $attributes->except('id') }}
 >
 @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
