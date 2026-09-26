@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-gold-400 text-start text-base font-medium text-primary-900 bg-gold-400 focus:outline-none focus:text-primary-900 focus:bg-gold-400 focus:border-gold-300 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-primary-100 hover:text-white hover:bg-primary-600 hover:border-primary-500 focus:outline-none focus:text-white focus:bg-primary-600 focus:border-primary-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
