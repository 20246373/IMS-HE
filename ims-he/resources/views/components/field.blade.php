@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => true, 'prefix' => null])

<div class="field">
 <label for="{{ $attributes->get('id', $name) }}" class="{{ $required ? 'required' : 'optional' }}">{{ $label }}</label>
 @if($prefix)
  <div class="input-with-prefix">
   <span>{{ $prefix }}</span>
   <input
    id="{{ $attributes->get('id', $name) }}"
    type="{{ $type }}"
    name="{{ $name }}"
    value="{{ old($name, $value) }}"
    @required($required)
    {{ $attributes->except('id') }}
   >
  </div>
 @else
  <input
   id="{{ $attributes->get('id', $name) }}"
   type="{{ $type }}"
   name="{{ $name }}"
   value="{{ old($name, $value) }}"
   @required($required)
   {{ $attributes->except('id') }}
  >
 @endif
 @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
