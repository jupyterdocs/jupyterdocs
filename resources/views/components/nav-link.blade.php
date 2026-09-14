@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-moss dark:border-sage text-sm font-display font-medium leading-5 text-pine dark:text-mint focus:outline-none focus:border-cypress dark:focus:border-mint transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-display font-medium leading-5 text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint hover:border-pine/20 dark:hover:border-mint/20 focus:outline-none focus:text-pine dark:focus:text-mint focus:border-pine/20 dark:focus:border-mint/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
