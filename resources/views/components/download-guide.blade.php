@props(['format' => 'pdf'])

{{-- Shown only on iPhone/iPad and Safari, where saving a file works differently from Chrome/Android. --}}
<details id="download-guide" hidden class="rounded-lg border border-moss/20 dark:border-sage/20 bg-jd-surface-2 dark:bg-cypress text-sm">
    <summary class="cursor-pointer select-none px-3 py-2.5 font-display font-semibold text-pine dark:text-mint">
        {{ __('How to save this file on your device') }}
    </summary>

    <div class="px-3 pb-3 space-y-3 text-pine dark:text-mint">
        <div data-guide="ios" hidden>
            <p class="font-display font-semibold text-xs uppercase tracking-wide text-moss dark:text-sage">{{ __('iPhone & iPad (Safari)') }}</p>
            <ol class="mt-1.5 space-y-1.5 list-decimal ps-5 text-jd-ink-muted dark:text-sage">
                <li>{{ __('Tap Download, then tap') }} <strong>{{ __('Download') }}</strong> {{ __('on the prompt Safari shows.') }}</li>
                <li>{{ __('Tap the') }} <strong>{{ __('download arrow') }}</strong> {{ __('that appears in Safari\'s address bar (or the') }} <strong>aA</strong> {{ __('menu → Downloads) and tap your file.') }}</li>
                <li>{{ __('To keep it, tap') }} <strong>{{ __('Share') }}</strong> {{ __('→') }} <strong>{{ __('Save to Files') }}</strong>. {{ __('Otherwise it stays in Files → Downloads.') }}</li>
            </ol>
            @if (in_array($format, ['docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls'], true))
                <p class="mt-2 text-xs text-jd-ink-muted dark:text-sage">
                    {{ __('Office files open in a quick viewer first. Use Share → Save to Files, or Open in Word / PowerPoint / Excel if you have the app.') }}
                </p>
            @endif
        </div>

        <div data-guide="app" hidden>
            <p class="font-display font-semibold text-xs uppercase tracking-wide text-moss dark:text-sage">{{ __('Using the installed app') }}</p>
            <p class="mt-1.5 text-jd-ink-muted dark:text-sage">
                {{ __('Downloads inside the home-screen app open in a viewer with no address bar. Tap the Share icon in that viewer and choose Save to Files. If nothing appears, open jupyterdocs.com in Safari and download from there.') }}
            </p>
        </div>

        <div data-guide="mac" hidden>
            <p class="font-display font-semibold text-xs uppercase tracking-wide text-moss dark:text-sage">{{ __('Safari on Mac') }}</p>
            <ol class="mt-1.5 space-y-1.5 list-decimal ps-5 text-jd-ink-muted dark:text-sage">
                <li>{{ __('Click Download. The file goes to your Downloads folder.') }}</li>
                <li>{{ __('Click the') }} <strong>{{ __('Downloads') }}</strong> {{ __('button in the Safari toolbar to open it.') }}</li>
                <li>{{ __('If nothing happens, allow downloads: Safari → Settings → Websites → Downloads → set jupyterdocs.com to Allow.') }}</li>
            </ol>
        </div>

        <p class="text-xs text-jd-ink-muted dark:text-sage">
            {{ __('Still stuck? Make sure you\'re not in Private Browsing or an in-app browser (Instagram, Facebook, etc.) — open the page in Safari itself.') }}
        </p>
    </div>
</details>

<script>
    (function () {
        var guide = document.getElementById('download-guide');
        if (! guide) return;

        var ua = navigator.userAgent;
        var isIos = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var isSafari = /safari/i.test(ua) && ! /chrome|chromium|crios|fxios|edg|android/i.test(ua);
        var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        if (! isIos && ! isSafari) return;

        // A page reload right after tapping Download can cancel iOS's save prompt.
        window.jdSkipReload = true;

        guide.hidden = false;

        var show = standalone ? 'app' : (isIos ? 'ios' : 'mac');
        var section = guide.querySelector('[data-guide="' + show + '"]');
        if (section) section.hidden = false;

        // Open the guide as soon as they tap Download, so it's there when the prompt appears.
        var form = guide.closest('div').querySelector('form[action*="/download"]');
        if (form) form.addEventListener('submit', function () { guide.open = true; });
    })();
</script>
