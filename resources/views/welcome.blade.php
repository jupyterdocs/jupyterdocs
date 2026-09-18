<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'JupyterDocs') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <meta name="description" content="Thousands of past papers, lecture notes, and study guides — searchable by anyone, unlocked by anyone who contributes.">

        <!-- Applied before paint so the stored theme choice never flashes the wrong one -->
        <script>
            (function () {
                try {
                    if (localStorage.getItem('jd-theme') === 'dark') {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,500;1,8..60,400&family=IBM+Plex+Mono:wght@400;500&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-display antialiased bg-jd-bg text-pine dark:bg-abyss dark:text-mint transition-colors">
        <x-brand-mark-defs />

        {{-- Same navigation partial as the rest of the app, so it never changes as you move from here into the archive. --}}
        @include('layouts.navigation')

        <!-- ============ Hero ============ -->
        <header class="relative overflow-hidden border-b border-pine/10 dark:border-mint/10">
            <svg class="absolute -top-[18%] -right-[14%] w-[62vw] max-w-[820px] min-w-[420px] opacity-[0.07] text-pine dark:text-mint pointer-events-none" viewBox="0 0 640 640" aria-hidden="true">
                <use href="#jupyterMark"/>
            </svg>

            <div class="relative max-w-6xl mx-auto px-5 pt-16 pb-14 sm:pt-24 sm:pb-20 grid grid-cols-1 md:grid-cols-[1.1fr_0.9fr] gap-10 md:gap-14 items-center">
                <div>
                    <p class="text-xs font-semibold tracking-[0.13em] uppercase text-jd-ink-muted dark:text-sage mb-3.5">Academic resource marketplace</p>
                    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight leading-[1.08] mb-4">
                        Share two. Unlock the whole archive.
                    </h1>
                    <p class="font-serif text-base sm:text-lg leading-relaxed text-jd-ink-muted dark:text-sage max-w-[46ch] mb-6">
                        Thousands of past papers, lecture notes, and study guides &mdash; searchable by anyone, unlocked by anyone who contributes two approved uploads of their own.
                    </p>
                    <div class="flex flex-wrap gap-3 mb-5">
                        <a href="{{ route('resources.create') }}" class="inline-flex items-center px-5 py-3 rounded-lg text-sm font-semibold bg-moss text-jd-bg hover:bg-cypress dark:bg-ember dark:text-pine dark:hover:bg-ember-bright transition">
                            Upload a document
                        </a>
                        <a href="{{ route('resources.index') }}" class="inline-flex items-center px-5 py-3 rounded-lg text-sm font-semibold border border-pine/20 dark:border-mint/20 hover:bg-jd-surface-2 dark:hover:bg-cypress transition">
                            Browse the archive
                        </a>
                    </div>
                    <p class="font-mono text-xs text-jd-ink-muted dark:text-sage">No account required to upload &middot; Reviewed within a day</p>
                </div>
                <div class="flex justify-center">
                    <svg class="w-64 sm:w-80" viewBox="0 0 640 640" style="filter: drop-shadow(0 20px 40px rgba(11,43,38,0.16))">
                        <title>JupyterDocs mark</title>
                        <use href="#jupyterMark"/>
                    </svg>
                </div>
            </div>
        </header>

        <main>
            <!-- ============ How it works ============ -->
            <section class="max-w-6xl mx-auto px-5 py-16 sm:py-20 border-b border-pine/10 dark:border-mint/10">
                <div class="max-w-[60ch] mb-10">
                    <p class="text-xs font-semibold tracking-[0.13em] uppercase text-jd-ink-muted dark:text-sage">How it works</p>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight mt-2">Three steps, no paywall</h2>
                    <p class="text-jd-ink-muted dark:text-sage mt-2.5 leading-relaxed">The archive grows because contributing is the price of admission &mdash; not a subscription.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-5">
                        <div class="w-8 h-8 rounded-full bg-jd-surface-2 dark:bg-cypress border border-pine/10 dark:border-mint/10 flex items-center justify-center text-sm font-bold text-moss dark:text-sage mb-4">1</div>
                        <h3 class="font-bold mb-2">Upload freely</h3>
                        <p class="text-sm text-jd-ink-muted dark:text-sage leading-relaxed">Any file &mdash; PDF, Word, PowerPoint, Excel, or plain text. Guests can upload too; no account required to start.</p>
                    </div>
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-5">
                        <div class="w-8 h-8 rounded-full bg-jd-surface-2 dark:bg-cypress border border-pine/10 dark:border-mint/10 flex items-center justify-center text-sm font-bold text-moss dark:text-sage mb-4">2</div>
                        <h3 class="font-bold mb-2">Reviewed, not gatekept</h3>
                        <p class="text-sm text-jd-ink-muted dark:text-sage leading-relaxed">An admin checks every upload before it goes public &mdash; usually within a day &mdash; so the archive stays clean without a bureaucracy.</p>
                    </div>
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-5">
                        <div class="w-8 h-8 rounded-full bg-jd-surface-2 dark:bg-cypress border border-pine/10 dark:border-mint/10 flex items-center justify-center text-sm font-bold text-moss dark:text-sage mb-4">3</div>
                        <h3 class="font-bold mb-2">Unlock at two</h3>
                        <p class="text-sm text-jd-ink-muted dark:text-sage leading-relaxed">Once you have 2 approved uploads, every download on JupyterDocs is yours &mdash; and your own uploads are always downloadable from day one.</p>
                    </div>
                </div>
            </section>

            <!-- ============ Archive preview ============ -->
            <section class="max-w-6xl mx-auto px-5 py-16 sm:py-20 border-b border-pine/10 dark:border-mint/10">
                <div class="max-w-[60ch] mb-10">
                    <p class="text-xs font-semibold tracking-[0.13em] uppercase text-jd-ink-muted dark:text-sage">The archive</p>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight mt-2">Thousands of documents, no faculty tree to click through</h2>
                    <p class="text-jd-ink-muted dark:text-sage mt-2.5 leading-relaxed">Full-text search and tags surface what you need, and approved PDFs open in a page-by-page reader right in the browser.</p>
                </div>

                <a href="{{ route('resources.index') }}" class="flex items-center gap-2.5 bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-xl px-4 py-3 mb-5 text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint transition">
                    <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <span class="text-sm">Search notes, papers, guides&hellip;</span>
                </a>

                <p class="text-[11px] uppercase tracking-[0.1em] font-semibold text-jd-ink-muted dark:text-sage mb-3">Example listings</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-[18px] flex flex-col gap-2.5">
                        <div class="font-bold text-sm leading-snug">Organic Chemistry &mdash; Midterm 2 Review</div>
                        <p class="font-serif text-sm text-jd-ink-muted dark:text-sage leading-relaxed">Annotated review covering reaction mechanisms and spectroscopy, drawn from three past exams.</p>
                        <div class="font-mono text-[11px] text-jd-ink-muted dark:text-sage">PDF &middot; 14 pages &middot; 312 downloads</div>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-xs text-jd-ink-muted dark:text-sage">A. Reyes</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-jd-success/15 dark:bg-sage/15 text-jd-success dark:text-sage">
                                <span class="w-1.5 h-1.5 rounded-full bg-jd-success dark:bg-sage"></span>Approved
                            </span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-[18px] flex flex-col gap-2.5">
                        <div class="font-bold text-sm leading-snug">Intro to Microeconomics &mdash; Lecture Set</div>
                        <p class="font-serif text-sm text-jd-ink-muted dark:text-sage leading-relaxed">Full semester of slide decks with margin notes on elasticity and market failure.</p>
                        <div class="font-mono text-[11px] text-jd-ink-muted dark:text-sage">PPTX &middot; 9 files &middot; 187 downloads</div>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-xs text-jd-ink-muted dark:text-sage">D. Okafor</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-jd-success/15 dark:bg-sage/15 text-jd-success dark:text-sage">
                                <span class="w-1.5 h-1.5 rounded-full bg-jd-success dark:bg-sage"></span>Approved
                            </span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 rounded-2xl p-[18px] flex flex-col gap-2.5">
                        <div class="font-bold text-sm leading-snug">Data Structures Final &mdash; Study Guide</div>
                        <p class="font-serif text-sm text-jd-ink-muted dark:text-sage leading-relaxed">Worked examples for trees, heaps, and graph traversal, with a mock exam at the end.</p>
                        <div class="font-mono text-[11px] text-jd-ink-muted dark:text-sage">PDF &middot; 22 pages &middot; 401 downloads</div>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-xs text-jd-ink-muted dark:text-sage">S. Lindqvist</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-jd-success/15 dark:bg-sage/15 text-jd-success dark:text-sage">
                                <span class="w-1.5 h-1.5 rounded-full bg-jd-success dark:bg-sage"></span>Approved
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ Closing CTA ============ -->
            <section class="max-w-6xl mx-auto px-5 py-16 sm:py-20">
                <div class="text-center bg-gradient-to-br from-jd-surface-2 to-white dark:from-cypress dark:to-pine rounded-3xl px-6 py-12 sm:py-16 flex flex-col items-center gap-4">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight max-w-[24ch]">Your notes are worth more shared.</h2>
                    <p class="text-jd-ink-muted dark:text-sage max-w-[40ch]">Upload one file, help unlock the archive for everyone else doing the same thing you are.</p>
                    <a href="{{ route('resources.create') }}" class="inline-flex items-center px-5 py-3 rounded-lg text-sm font-semibold bg-moss text-jd-bg hover:bg-cypress dark:bg-ember dark:text-pine dark:hover:bg-ember-bright transition mt-1.5">
                        Upload a document
                    </a>
                </div>
            </section>
        </main>

        <footer class="px-5 py-9 border-t border-pine/10 dark:border-mint/10">
            <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <svg class="w-5 h-5" viewBox="0 0 640 640"><use href="#jupyterMark"/></svg>
                    Jupyter<span class="opacity-60">Docs</span>
                </div>
                <span class="font-serif italic text-sm text-jd-ink-muted dark:text-sage">Notes orbit. Knowledge compounds.</span>
            </div>
        </footer>
    </body>
</html>
