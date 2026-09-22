@props(['driver'])

@php
    $labels = [
        'cloudconvert' => __('CloudConvert (API)'),
        'gotenberg' => __('Gotenberg (API)'),
        'local' => __('This device'),
    ];
    $classes = [
        'cloudconvert' => 'bg-jd-success/10 text-jd-success',
        'gotenberg' => 'bg-jd-success/10 text-jd-success',
        'local' => 'bg-moss/10 text-moss dark:bg-sage/10 dark:text-sage',
    ];
@endphp

<span class="shrink-0 text-xs font-display font-semibold px-2.5 py-1 rounded-full {{ $classes[$driver] ?? 'bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage' }}">
    {{ $labels[$driver] ?? ucfirst($driver ?? __('unknown')) }}
</span>
