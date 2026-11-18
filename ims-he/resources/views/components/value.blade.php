@props(['label', 'hint' => null])

{{-- A value that cannot be edited here, shown as plain text in the same spot a field would be (no grayed-out box). --}}
<div class="field">
 <span class="field-label">{{ $label }}</span>
 <div class="field-value">{{ $slot }}</div>
 @if($hint)<div class="field-hint">{{ $hint }}</div>@endif
</div>
