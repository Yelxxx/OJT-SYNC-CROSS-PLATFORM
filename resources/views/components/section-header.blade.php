@props(['title', 'subtitle' => null])

<div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
        <h3 class="text-lg font-semibold text-primary-900">{{ $title }}</h3>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">
            {{ $action }}
        </div>
    @endisset
</div>
