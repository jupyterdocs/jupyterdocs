@props(['compact' => false])

<footer {{ $attributes->class($compact ? ['px-5 py-6'] : ['px-5 py-9 border-t border-pine/10 dark:border-mint/10']) }}>
    <div class="max-w-6xl mx-auto flex flex-col gap-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 font-display font-bold text-sm text-pine dark:text-mint">
                <svg class="w-5 h-5" viewBox="0 0 640 640"><use href="#jupyterMark"/></svg>
                Jupyter<span class="opacity-60">Docs</span>
            </div>

            @unless ($compact)
                <span class="font-serif italic text-sm text-jd-ink-muted dark:text-sage">Notes orbit. Knowledge compounds.</span>
            @endunless
        </div>

        <nav aria-label="Legal" class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-display font-medium text-jd-ink-muted dark:text-sage">
            <a href="{{ route('terms') }}" class="hover:text-pine dark:hover:text-mint transition">{{ __('Terms of Service') }}</a>
            <a href="{{ route('privacy') }}" class="hover:text-pine dark:hover:text-mint transition">{{ __('Privacy Policy') }}</a>
            <a href="{{ route('copyright') }}" class="hover:text-pine dark:hover:text-mint transition">{{ __('Copyright Policy') }}</a>
            <a href="{{ route('contact') }}" class="hover:text-pine dark:hover:text-mint transition">{{ __('Contact') }}</a>
            <span class="text-jd-ink-muted/60 dark:text-sage/60">&copy; {{ now()->year }} JupyterDocs</span>
        </nav>
    </div>
</footer>
