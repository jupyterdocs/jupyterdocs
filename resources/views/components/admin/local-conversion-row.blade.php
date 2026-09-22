@props(['doc'])

@php
    $labels = [
        'pending' => __('Waiting'),
        'processing' => __('Converting…'),
        'done' => __('Done'),
        'failed' => __('Failed'),
    ];
    $classes = [
        'pending' => 'bg-jd-warning/10 text-jd-warning',
        'processing' => 'bg-jd-warning/10 text-jd-warning',
        'done' => 'bg-jd-success/10 text-jd-success',
        'failed' => 'bg-jd-danger/10 text-jd-danger',
    ];
    $status = $doc['status'] ?? 'pending';
@endphp

<li class="p-3 flex items-center justify-between gap-3 text-sm" data-doc-id="{{ $doc['id'] }}">
    <div class="min-w-0">
        <div class="font-display font-medium text-pine dark:text-mint truncate">{{ $doc['title'] }}</div>
        <div class="text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage">{{ $doc['format'] }}</div>
        @if (($doc['error'] ?? null) && $status === 'failed')
            <div class="text-xs text-jd-danger mt-0.5">{{ \Illuminate\Support\Str::limit($doc['error'], 100) }}</div>
        @endif
    </div>
    <span data-doc-status class="shrink-0 inline-flex items-center gap-1.5 text-xs font-display font-semibold px-2.5 py-1 rounded-full {{ $classes[$status] ?? $classes['pending'] }}">
        {{ $labels[$status] ?? ucfirst($status) }}
    </span>
</li>
