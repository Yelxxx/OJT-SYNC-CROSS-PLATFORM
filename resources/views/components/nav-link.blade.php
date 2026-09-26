@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-primary-900 bg-gold-400 hover:bg-gold-500 focus:outline-none focus:bg-gold-500 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-primary-100 hover:text-white hover:bg-primary-700/60 focus:outline-none focus:text-white focus:bg-primary-700/60 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
