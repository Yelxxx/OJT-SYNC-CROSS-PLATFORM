@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-primary-800']) }}>
    {{ $value ?? $slot }}
</label>
