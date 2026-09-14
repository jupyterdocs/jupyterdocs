@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-moss text-sm font-display font-medium leading-5 text-pine focus:outline-none focus:border-cypress transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-display font-medium leading-5 text-jd-ink-muted hover:text-pine hover:border-pine/20 focus:outline-none focus:text-pine focus:border-pine/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
