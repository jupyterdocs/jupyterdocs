@props(['label', 'value', 'accent' => null, 'href' => null])

@php
    $valueClass = match ($accent) {
        'jd-success' => 'text-jd-success',
        'jd-warning' => 'text-jd-warning',
        'jd-danger' => 'text-jd-danger',
        default => 'text-pine dark:text-mint',
    };
    $classes = 'block bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4'
        .($href ? ' hover:border-pine/25 dark:hover:border-mint/25 transition' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}">
@else
    <div class="{{ $classes }}">
@endif
        <div class="text-xs font-display font-medium text-jd-ink-muted dark:text-sage uppercase tracking-wide">{{ $label }}</div>
        <div class="mt-1 text-2xl font-display font-bold {{ $valueClass }}">{{ number_format($value, floor($value) == $value ? 0 : 1) }}</div>
@if ($href)
    </a>
@else
    </div>
@endif
