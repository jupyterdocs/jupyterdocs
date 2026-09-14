@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-pine/15 text-pine placeholder:text-jd-ink-muted/60 focus:border-moss focus:ring-moss rounded-lg shadow-sm font-display']) }}>
