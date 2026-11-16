@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-primary-950 bg-gold-400 shadow-sm transition-all duration-150 ease-in-out'
    : 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-100 hover:text-white hover:bg-emerald-700/60 transition-all duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
