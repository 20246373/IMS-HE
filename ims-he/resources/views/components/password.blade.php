@props(['label' => 'Password', 'name' => 'password', 'id' => null, 'hint' => null])
@php($id = $id ?? $name)

{{-- Password input with a show/hide toggle inside the field, and an optional gray hint below it. --}}
<div class="field">
 <label for="{{ $id }}">{{ $label }}</label>
 <div class="password-wrap">
  <input id="{{ $id }}" type="password" name="{{ $name }}" required {{ $attributes }} @if($hint) aria-describedby="{{ $id }}-hint" @endif>
  <button type="button" class="password-toggle" aria-label="Show password" aria-controls="{{ $id }}"
          onclick="var i=document.getElementById('{{ $id }}'),s=i.type==='password';i.type=s?'text':'password';this.setAttribute('aria-label',s?'Hide password':'Show password');this.classList.toggle('shown',s)">
   <svg class="eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
   <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.1-5.9M9.9 5.2A9.6 9.6 0 0 1 12 5c7 0 11 7 11 7a18.6 18.6 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2"/><path d="M1 1l22 22"/></svg>
  </button>
 </div>
 @if($hint)<div class="field-hint" id="{{ $id }}-hint">{{ $hint }}</div>@endif
 @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
