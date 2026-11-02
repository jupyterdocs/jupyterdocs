@props(['size' => 6, 'label' => null])

<div {{ $attributes->merge(['class' => 'inline-flex flex-col items-center gap-3']) }} role="status" aria-live="polite">
    <div class="jd-planet" style="--jd-planet-size: {{ $size }}rem" aria-hidden="true">
        <span class="jd-planet__star"></span>
        <span class="jd-planet__star"></span>
        <span class="jd-planet__star"></span>
        <span class="jd-planet__ring jd-planet__ring--back"></span>
        <span class="jd-planet__core"></span>
        <span class="jd-planet__ring jd-planet__ring--front"></span>
        <span class="jd-planet__orbit jd-planet__orbit--a"><span class="jd-planet__moon"></span></span>
        <span class="jd-planet__orbit jd-planet__orbit--b"><span class="jd-planet__moon"></span></span>
    </div>
    @if ($label)
        <p data-loader-label class="text-sm text-jd-ink-muted dark:text-sage font-serif italic text-center">{{ $label }}</p>
    @else
        <span class="sr-only">{{ __('Loading…') }}</span>
    @endif
</div>
