@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-pine font-display']) }}>
    {{ $value ?? $slot }}
</label>
