<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="login-url" content="{{ route('login') }}">

        @php
            $metaTitle = $title ? $title.' · '.config('app.name', 'JupyterDocs') : config('app.name', 'JupyterDocs').' — Free Lecture Notes, Past Papers & Study Guides';
            $metaDescription = $description ?? 'Search thousands of student-uploaded lecture notes, past exam papers, and study guides on JupyterDocs. Upload two documents to unlock free downloads.';
            $metaCanonical = $canonical ?? url()->current();
        @endphp

        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $metaCanonical }}">
        @if ($noindex)
            <meta name="robots" content="noindex, follow">
        @endif

        <meta property="og:type" content="{{ $type }}">
        <meta property="og:site_name" content="{{ config('app.name', 'JupyterDocs') }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $metaCanonical }}">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
        @endif
        <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        @if ($image)
            <meta name="twitter:image" content="{{ $image }}">
        @endif
        @isset($structuredData)
            {!! $structuredData !!}
        @endisset

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

                // Only the installed, standalone app gets a boot splash, and
                // only once per browser session — not on every internal link.
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

        <div class="min-h-screen bg-jd-bg dark:bg-abyss transition-colors pb-16 sm:pb-0">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-pine border-b border-pine/10 dark:border-mint/10">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @include('layouts.bottom-nav')
    </body>
</html>
