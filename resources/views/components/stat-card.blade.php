@props(['label', 'value', 'icon' => null, 'tone' => 'primary', 'hint' => null])

@php
    $tones = [
        'primary' => ['tile' => 'bg-primary-100 text-primary-600', 'value' => 'text-primary-700'],
        'gold' => ['tile' => 'bg-gold-100 text-gold-600', 'value' => 'text-gold-600'],
        'gray' => ['tile' => 'bg-gray-100 text-gray-500', 'value' => 'text-gray-900'],
        'green' => ['tile' => 'bg-emerald-100 text-emerald-600', 'value' => 'text-emerald-600'],
    ];
    $tone = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-primary-100 shadow-sm p-5 flex items-start gap-4']) }}>
    @if ($icon)
        <span class="shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-lg {{ $tone['tile'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold leading-none {{ $tone['value'] }}">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
        @endif
    </div>
</div>
