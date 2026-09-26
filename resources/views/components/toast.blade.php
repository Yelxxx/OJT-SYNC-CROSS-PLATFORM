@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-primary-600 text-white',
        'error' => 'bg-rose-600 text-white',
    ];
    $icons = [
        'success' => 'check-circle',
        'error' => 'x-circle',
    ];
@endphp

<div x-cloak
     x-data="{ show: false }"
     x-show="show"
     x-init="setTimeout(() => show = true, 100); setTimeout(() => show = false, 4500)"
     @click="show = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed right-4 top-4 z-50 w-[calc(100%-2rem)] max-w-sm cursor-pointer rounded-lg px-4 py-3 shadow-lg sm:w-auto {{ $styles[$type] }}">
    <div class="flex items-center gap-3">
        <x-icon :name="$icons[$type]" class="h-5 w-5 shrink-0" />
        <p class="text-sm font-medium">{{ $slot }}</p>
        <span class="ms-1 text-lg leading-none opacity-70 hover:opacity-100" aria-hidden="true">&times;</span>
    </div>
</div>
