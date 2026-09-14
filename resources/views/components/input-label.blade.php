@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-pine dark:text-mint font-display']) }}>
    {{ $value ?? $slot }}
</label>
