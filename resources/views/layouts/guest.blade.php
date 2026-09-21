<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · '.config('app.name', 'JupyterDocs') : config('app.name', 'JupyterDocs') }}</title>
        @if ($noindex)
            <meta name="robots" content="noindex, follow">
        @endif
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        @include('layouts.pwa-head')

        <!-- Applied before paint so the stored theme choice never flashes the wrong one -->
        <script>
            (function () {
                try {
                    if (localStorage.getItem('jd-theme') === 'dark') {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}

                try {
                    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                    if (standalone && ! sessionStorage.getItem('jd-splash-shown')) {
                        document.documentElement.classList.add('show-splash');
                        sessionStorage.setItem('jd-splash-shown', '1');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,500;1,8..60,400&family=IBM+Plex+Mono:wght@400;500&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-display text-pine dark:text-mint antialiased">
        <x-brand-mark-defs />
        <x-pwa-splash />

        <div class="min-h-screen flex flex-col bg-jd-bg dark:bg-abyss transition-colors">
            <header class="safe-top px-6 py-5 flex items-center justify-between">
                <a href="/" class="inline-flex items-center gap-2 text-pine dark:text-mint">
                    <svg class="w-7 h-7" viewBox="0 0 640 640"><use href="#jupyterMarkFlat"/></svg>
                    <span class="font-bold text-base">Jupyter<span class="text-jd-ink-muted dark:text-sage font-medium">Docs</span></span>
                </a>
                <button
                    type="button"
                    id="theme-toggle"
                    aria-label="Toggle light and dark theme"
                    class="flex items-center gap-1.5 rounded-full border border-pine/15 dark:border-mint/15 bg-jd-surface-2 dark:bg-cypress px-3 py-1.5 text-xs font-display font-medium text-pine dark:text-mint"
                >
                    <span id="theme-toggle-label">Dark</span>
                </button>
            </header>

            <div class="flex-1 flex flex-col items-center justify-center px-6 pb-12">
                <svg class="w-16 h-16 mb-6" viewBox="0 0 640 640" style="filter: drop-shadow(0 12px 24px rgba(11,43,38,0.16))"><use href="#jupyterMark"/></svg>

                <div class="w-full sm:max-w-md bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-[0_16px_40px_rgba(11,43,38,0.10)] rounded-2xl px-6 py-6 sm:px-8 sm:py-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-xs text-jd-ink-muted dark:text-sage font-mono">Academic resource marketplace</p>
            </div>
        </div>

        <script>
            (function () {
                var root = document.documentElement;
                var btn = document.getElementById('theme-toggle');
                var label = document.getElementById('theme-toggle-label');
                var STORE_KEY = 'jd-theme';

                function apply(theme) {
                    root.classList.toggle('dark', theme !== 'light');
                    if (label) label.textContent = theme === 'light' ? 'Light' : 'Dark';
                }

                apply(root.classList.contains('dark') ? 'dark' : 'light');

                if (btn) {
                    btn.addEventListener('click', function () {
                        var next = root.classList.contains('dark') ? 'light' : 'dark';
                        apply(next);
                        try { localStorage.setItem(STORE_KEY, next); } catch (e) {}
                    });
                }
            })();
        </script>
    </body>
</html>
