@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => true, 'prefix' => null, 'inlineError' => true])

<div class="field">
 <label for="{{ $attributes->get('id', $name) }}" class="{{ $attributes->has('readonly') ? '' : ($required ? 'required' : 'optional') }}">{{ $label }}</label>
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
 {{-- inline-error="false" when the page already shows the message in the alert above (e.g. the login form) --}}
 @if($inlineError)
  @error($name)<div class="field-error">{{ $message }}</div>@enderror
 @endif
</div>
