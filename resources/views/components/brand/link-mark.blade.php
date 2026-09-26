@props([
    'variant' => 'color', // color | light
])

@php
    // Two interlocked chain links on the diagonal — the bond between
    // student (dark link) and company (light blue link).
    $dark = $variant === 'light' ? '#ffffff' : '#0b0b0f';
    $blue = $variant === 'light' ? '#7dd3fc' : '#38bdf8';
@endphp

<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" fill="none" role="img" aria-label="OJT Sync logo" {{ $attributes }}>
    <g transform="rotate(45 32 32)">
        {{-- Student link --}}
        <ellipse cx="32" cy="24" rx="24" ry="10.5" stroke="{{ $dark }}" stroke-width="5" />
        {{-- Company link (drawn on top to suggest interlocking) --}}
        <ellipse cx="32" cy="40" rx="24" ry="10.5" stroke="{{ $blue }}" stroke-width="5" />
    </g>
</svg>
