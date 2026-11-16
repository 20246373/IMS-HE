@props(['type' => 'success', 'title' => null])

@php
    $type = in_array($type, ['success', 'error', 'warning'], true) ? $type : 'success';
@endphp

<div {{ $attributes->merge(['class' => "alert alert-{$type}"]) }} role="alert">
 <span class="alert-icon" aria-hidden="true">
  @if($type === 'success')
   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
  @elseif($type === 'error')
   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
  @else
   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>
  @endif
 </span>
 <div class="alert-body">
  @if($title)<strong>{{ $title }}</strong>@endif
  <div>{{ $slot }}</div>
 </div>
 <button type="button" class="alert-dismiss" aria-label="Dismiss alert" onclick="this.closest('[role=alert]').remove()">×</button>
</div>
