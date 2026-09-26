@props(['class' => '', 'flush' => false, 'shadow' => true])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden border border-primary-100 rounded-xl ' . ($shadow ? 'shadow-sm' : '') . ' ' . $class]) }}>
    @if ($flush)
        {{ $slot }}
    @else
        <div class="p-6">
            {{ $slot }}
        </div>
    @endif
</div>
