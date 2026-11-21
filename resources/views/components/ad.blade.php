@props(['placement' => 'document'])

@php
    $client = config('ads.client');
    $unit = config("ads.slots.{$placement}");
    $show = config('ads.enabled')
        && $client
        && $unit
        && ! (config('ads.hide_for_admins') && auth()->user()?->isAdmin());
@endphp

@if ($show)
    {{-- Clearly labelled, and kept out of the reading experience: ads only ever sit
         beside or below the document, never over it or inside the full-screen reader. --}}
    <aside {{ $attributes->merge(['class' => 'jd-ad']) }} data-ad aria-label="{{ __('Advertisement') }}">
        <p class="jd-ad__label">{{ __('Advertisement') }}</p>
        <ins class="adsbygoogle block"
             data-ad-client="{{ $client }}"
             data-ad-slot="{{ $unit }}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
    </aside>

    @once
        {{-- AdSense is third-party JavaScript, so it never competes with the page for
             bandwidth: it loads only after the page has finished loading and the
             browser is idle, and each ad is only requested when it is about to scroll
             into view. --}}
        <script>
            (function () {
                var ads = document.querySelectorAll('[data-ad]');
                if (!ads.length) return;

                function requestAd(el) {
                    try {
                        (window.adsbygoogle = window.adsbygoogle || []).push({});
                    } catch (e) {}
                }

                function start() {
                    var s = document.createElement('script');
                    s.async = true;
                    s.crossOrigin = 'anonymous';
                    s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $client }}';
                    document.head.appendChild(s);

                    if (!('IntersectionObserver' in window)) {
                        ads.forEach(requestAd);
                        return;
                    }

                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            io.unobserve(entry.target);
                            requestAd(entry.target);
                        });
                    }, { rootMargin: '300px 0px' });

                    ads.forEach(function (ad) { io.observe(ad); });
                }

                function whenIdle() {
                    if ('requestIdleCallback' in window) {
                        window.requestIdleCallback(start, { timeout: 4000 });
                    } else {
                        window.setTimeout(start, 1500);
                    }
                }

                if (document.readyState === 'complete') whenIdle();
                else window.addEventListener('load', whenIdle);
            })();
        </script>
    @endonce
@endif
