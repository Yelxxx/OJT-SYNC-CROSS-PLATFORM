@props(['icon' => 'inbox', 'title' => null, 'message' => null])

<div {{ $attributes->merge(['class' => 'py-14 px-6 text-center']) }}>
    <span class="mx-auto inline-flex items-center justify-center h-14 w-14 rounded-full bg-primary-50 text-primary-300">
        <x-icon :name="$icon" class="h-7 w-7" />
    </span>
    @if ($title)
        <h3 class="mt-4 text-base font-semibold text-gray-900">{{ $title }}</h3>
    @endif
    @if ($message)
        <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">{{ $message }}</p>
    @endif
    @isset($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endisset
</div>
