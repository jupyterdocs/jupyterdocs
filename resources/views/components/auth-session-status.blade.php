@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-jd-success']) }}>
        {{ $status }}
    </div>
@endif
