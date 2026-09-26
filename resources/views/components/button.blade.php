@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'size' => 'md'])

@php
    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'gold' => 'bg-gold-500 text-primary-900 hover:bg-gold-400 focus:ring-gold-500',
        'secondary' => 'bg-white text-primary-700 border border-primary-300 hover:bg-primary-50 focus:ring-primary-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500',
        'ghost' => 'text-primary-600 hover:text-primary-800 hover:bg-primary-50 focus:ring-primary-500',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-xs',
        'lg' => 'px-6 py-3 text-sm',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold uppercase tracking-widest transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 ' . $sizes[$size] . ' ' . $variants[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
