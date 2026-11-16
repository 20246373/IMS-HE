@props(['type' => 'success'])
{{-- Shared alert: type is "success" (green) or "error" (red). Styles .ok / .err live in layouts/app. --}}
<div {{ $attributes->class(['ok' => $type === 'success', 'err' => $type === 'error']) }} role="alert">{{ $slot }}</div>
