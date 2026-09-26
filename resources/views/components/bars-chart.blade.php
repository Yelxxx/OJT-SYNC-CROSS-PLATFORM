@props(['items' => [], 'color' => 'bg-primary-500', 'trackClass' => 'bg-cream-100'])

@php
    $items = collect($items);
    $max = max(1, (int) $items->max('value'));
@endphp

@if ($items->isEmpty())
    <p class="py-8 text-center text-sm text-gray-500">No data yet.</p>
@else
    <div class="space-y-4">
        @foreach ($items as $item)
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-3">
                    <span class="truncate text-sm font-medium text-gray-700">{{ $item['label'] }}</span>
                    <span class="shrink-0 text-sm font-bold text-primary-900">{{ $item['value'] }}</span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full {{ $trackClass }}">
                    <div class="h-2.5 rounded-full transition-all duration-700 {{ $color }}" style="width: {{ round(($item['value'] / $max) * 100) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif
