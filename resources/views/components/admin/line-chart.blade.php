@props(['labels' => [], 'values' => [], 'color' => 'moss', 'height' => 160])

@php
    $labels = array_values($labels);
    $values = array_values($values);
    $n = count($values);
    $max = $n ? max(1, max($values)) : 1;

    $points = [];
    foreach ($values as $i => $v) {
        $x = $n > 1 ? round(($i / ($n - 1)) * 100, 2) : 50;
        $y = round(95 - ($v / $max) * 85, 2);
        $points[] = ['x' => $x, 'y' => $y, 'value' => $v, 'label' => $labels[$i] ?? ''];
    }

    $polyline = collect($points)->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
    $areaPath = $n ? 'M '.$points[0]['x'].',100 L '.$polyline.' L '.$points[$n - 1]['x'].',100 Z' : '';

    $strokeClass = match ($color) {
        'jd-success' => 'stroke-jd-success',
        'jd-warning' => 'stroke-jd-warning',
        'jd-danger' => 'stroke-jd-danger',
        default => 'stroke-moss dark:stroke-sage',
    };
    $fillClass = match ($color) {
        'jd-success' => 'fill-jd-success',
        'jd-warning' => 'fill-jd-warning',
        'jd-danger' => 'fill-jd-danger',
        default => 'fill-moss dark:fill-sage',
    };
    $dotClass = match ($color) {
        'jd-success' => 'bg-jd-success',
        'jd-warning' => 'bg-jd-warning',
        'jd-danger' => 'bg-jd-danger',
        default => 'bg-moss dark:bg-sage',
    };

    // Pick a handful of evenly-spaced label indices, always keeping the last
    // one but dropping its neighbor if they'd be too close and overlap.
    $maxLabels = min($n, 6);
    $labelIndices = [];
    if ($maxLabels > 0) {
        $step = $n > 1 ? max(1, (int) floor(($n - 1) / max(1, $maxLabels - 1))) : 1;
        for ($i = 0; $i < $n; $i += $step) {
            $labelIndices[] = $i;
        }
        $lastIndex = $n - 1;
        if (end($labelIndices) !== $lastIndex) {
            if (count($labelIndices) > 1 && ($lastIndex - end($labelIndices)) < $step) {
                array_pop($labelIndices);
            }
            $labelIndices[] = $lastIndex;
        }
    }
@endphp

<div {{ $attributes }}>
    @if ($n === 0)
        <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('No data yet.') }}</p>
    @else
        <div class="relative w-full" style="height: {{ $height }}px">
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 w-full h-full">
                <path d="{{ $areaPath }}" class="{{ $fillClass }} opacity-10" stroke="none"></path>
                <polyline points="{{ $polyline }}" fill="none" class="{{ $strokeClass }}" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"></polyline>
            </svg>
            <div class="absolute inset-0">
                @foreach ($points as $p)
                    <span
                        class="absolute w-2 h-2 -translate-x-1/2 -translate-y-1/2 rounded-full {{ $dotClass }} ring-2 ring-white dark:ring-pine"
                        style="left: {{ $p['x'] }}%; top: {{ $p['y'] }}%"
                        title="{{ $p['label'] }}: {{ number_format($p['value']) }}"
                    ></span>
                @endforeach
            </div>
        </div>
        <div class="relative h-4 mt-1">
            @foreach ($points as $i => $p)
                @if (in_array($i, $labelIndices, true))
                    <span class="absolute -translate-x-1/2 text-[10px] font-mono text-jd-ink-muted dark:text-sage whitespace-nowrap" style="left: {{ $p['x'] }}%">{{ $p['label'] }}</span>
                @endif
            @endforeach
        </div>
    @endif
</div>
