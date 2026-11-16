@props(['label', 'name', 'required' => true])

<div class="field">
 <label for="{{ $attributes->get('id', $name) }}" class="{{ $required ? 'required' : 'optional' }}">{{ $label }}</label>
 <select
  id="{{ $attributes->get('id', $name) }}"
  name="{{ $name }}"
  @required($required)
  {{ $attributes->except('id') }}
 >{{ $slot }}</select>
 @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
