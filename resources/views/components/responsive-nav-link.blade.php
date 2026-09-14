@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-moss text-start text-base font-display font-medium text-pine bg-jd-surface-2 focus:outline-none focus:text-pine focus:bg-jd-surface-2 focus:border-cypress transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-display font-medium text-jd-ink-muted hover:text-pine hover:bg-jd-surface-2 hover:border-pine/20 focus:outline-none focus:text-pine focus:bg-jd-surface-2 focus:border-pine/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
