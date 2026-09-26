@props([
    'variant' => 'color', // color | light (accepted for compatibility; PNG renders as-is)
])

<img
    src="{{ asset('images/logo.png') }}"
    alt="OJT Sync logo"
    {{ $attributes }}
/>
