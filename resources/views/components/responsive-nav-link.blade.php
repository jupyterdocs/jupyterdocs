@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-moss dark:border-sage text-start text-base font-display font-medium text-pine dark:text-mint bg-jd-surface-2 dark:bg-cypress focus:outline-none focus:text-pine dark:focus:text-mint focus:bg-jd-surface-2 dark:focus:bg-cypress focus:border-cypress dark:focus:border-mint transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-display font-medium text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint hover:bg-jd-surface-2 dark:hover:bg-cypress hover:border-pine/20 dark:hover:border-mint/20 focus:outline-none focus:text-pine dark:focus:text-mint focus:bg-jd-surface-2 dark:focus:bg-cypress focus:border-pine/20 dark:focus:border-mint/20 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
