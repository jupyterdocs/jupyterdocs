<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'JupyterDocs') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,500;1,8..60,400&family=IBM+Plex+Mono:wght@400;500&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-display text-pine antialiased">
        <x-brand-mark-defs />

        <div class="min-h-screen flex flex-col bg-jd-bg">
            <header class="px-6 py-5">
                <a href="/" class="inline-flex items-center gap-2 text-pine">
                    <svg class="w-7 h-7" viewBox="0 0 640 640"><use href="#jupyterMark"/></svg>
                    <span class="font-bold text-base">Jupyter<span class="text-jd-ink-muted font-medium">Docs</span></span>
                </a>
            </header>

            <div class="flex-1 flex flex-col items-center justify-center px-6 pb-12">
                <svg class="w-16 h-16 mb-6" viewBox="0 0 640 640" style="filter: drop-shadow(0 12px 24px rgba(11,43,38,0.16))"><use href="#jupyterMark"/></svg>

                <div class="w-full sm:max-w-md bg-white border border-pine/10 shadow-[0_16px_40px_rgba(11,43,38,0.10)] rounded-2xl px-6 py-6 sm:px-8 sm:py-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-xs text-jd-ink-muted font-mono">Academic resource marketplace</p>
            </div>
        </div>
    </body>
</html>
