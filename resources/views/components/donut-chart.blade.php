@props([
    'percent' => 0,
    'size' => 140,
    'stroke' => 14,
    'center' => null,
    'caption' => null,
    'trackClass' => 'text-primary-100',
    'barClass' => 'text-primary-600',
])

@php
    $pct = min(100, max(0, (int) $percent));
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference * (1 - $pct / 100);
@endphp

<div class="inline-flex flex-col items-center">
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
            <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" class="stroke-current {{ $trackClass }}" />
            <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" class="stroke-current {{ $barClass }} transition-all duration-700" />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            @if ($center)
                <span class="text-2xl font-bold leading-none text-primary-900">{{ $center }}</span>
            @endif
            @if ($caption)
                <span class="mt-1.5 text-xs font-medium text-gray-500">{{ $caption }}</span>
            @endif
        </div>
    </div>
</div>
