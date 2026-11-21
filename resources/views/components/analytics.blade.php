@php
    $analyticsId = config('site.analytics_id');
    $showAnalytics = $analyticsId
        && preg_match('/^(G|GT)-[A-Z0-9]+$/', $analyticsId)
        // Admin browsing would only pollute the numbers.
        && ! auth()->user()?->isAdmin();
@endphp

@if ($showAnalytics)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', '{{ $analyticsId }}');
    </script>
@endif
