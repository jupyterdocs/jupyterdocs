<nav x-data="{ open: false }" class="safe-top bg-jd-bg/90 dark:bg-abyss/90 backdrop-blur border-b border-pine/10 dark:border-mint/10 sticky top-0 z-20">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-pine dark:text-mint">
                        <svg class="w-7 h-7" viewBox="0 0 640 640"><use href="#jupyterMarkFlat"/></svg>
                        <span class="font-display font-bold text-base hidden sm:inline">Jupyter<span class="text-jd-ink-muted dark:text-sage font-medium">Docs</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.index')">
                        {{ __('Browse') }}
                    </x-nav-link>
                    <x-nav-link :href="route('resources.create')" :active="request()->routeIs('resources.create')">
                        {{ __('Upload') }}
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('resources.mine')" :active="request()->routeIs('resources.mine')">
                            {{ __('My Uploads') }}
                        </x-nav-link>
                        <x-nav-link :href="route('resources.saved')" :active="request()->routeIs('resources.saved')">
                            {{ __('Saved') }}
                        </x-nav-link>
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                                {{ __('Admin') }}
                            </x-nav-link>
                            <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                                {{ __('Reports') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                <button
                    type="button"
                    id="pwa-install"
                    hidden
                    class="flex items-center gap-1.5 rounded-full border border-moss/30 dark:border-sage/30 bg-moss/10 dark:bg-sage/10 px-3 py-1.5 text-xs font-display font-semibold text-moss dark:text-sage hover:bg-moss/20 dark:hover:bg-sage/20"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                    {{ __('Install app') }}
                </button>

                <button
                    type="button"
                    id="theme-toggle"
                    aria-label="Toggle light and dark theme"
                    class="flex items-center gap-1.5 rounded-full border border-pine/15 dark:border-mint/15 bg-jd-surface-2 dark:bg-cypress px-3 py-1.5 text-xs font-display font-medium text-pine dark:text-mint"
                >
                    <span id="theme-toggle-label">Dark</span>
                </button>

                @auth
                    <x-dropdown align="right" width="48" content-classes="py-1 bg-white dark:bg-pine">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-display font-medium rounded-md text-jd-ink-muted dark:text-sage bg-jd-bg dark:bg-abyss hover:text-pine dark:hover:text-mint focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-display text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-moss text-jd-bg rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:bg-ember dark:text-pine dark:hover:bg-ember-bright transition">{{ __('Register') }}</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden gap-1">
                <button
                    type="button"
                    id="pwa-install-mobile"
                    hidden
                    aria-label="Install app"
                    class="inline-flex items-center justify-center p-2 rounded-md text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint hover:bg-jd-surface-2 dark:hover:bg-cypress focus:outline-none transition duration-150 ease-in-out"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                </button>
                <button
                    type="button"
                    id="theme-toggle-mobile"
                    aria-label="Toggle light and dark theme"
                    class="inline-flex items-center justify-center p-2 rounded-md text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint hover:bg-jd-surface-2 dark:hover:bg-cypress focus:outline-none transition duration-150 ease-in-out"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint hover:bg-jd-surface-2 dark:hover:bg-cypress focus:outline-none focus:bg-jd-surface-2 dark:focus:bg-cypress transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.index')">
                {{ __('Browse') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('resources.create')" :active="request()->routeIs('resources.create')">
                {{ __('Upload') }}
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('resources.mine')" :active="request()->routeIs('resources.mine')">
                    {{ __('My Uploads') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resources.saved')" :active="request()->routeIs('resources.saved')">
                    {{ __('Saved') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Admin') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                        {{ __('Reports') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-pine/10 dark:border-mint/10">
            @auth
                <div class="px-4">
                    <div class="font-display font-medium text-base text-pine dark:text-mint">{{ Auth::user()->name }}</div>
                    <div class="font-display font-medium text-sm text-jd-ink-muted dark:text-sage">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="px-4 space-y-1">
                    <x-responsive-nav-link :href="route('login')">{{ __('Log in') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">{{ __('Register') }}</x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>

<!-- iOS has no install prompt API, so this walks people through Safari's Add to Home Screen. -->
<div id="ios-install-sheet" hidden class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 p-5 shadow-xl safe-bottom">
        <div class="flex items-start justify-between gap-3">
            <h2 class="font-display font-bold text-base text-pine dark:text-mint">{{ __('Install JupyterDocs') }}</h2>
            <button type="button" data-ios-close aria-label="{{ __('Close') }}" class="text-jd-ink-muted dark:text-sage text-xl leading-none">&times;</button>
        </div>
        <p id="ios-install-inapp" hidden class="mt-3 rounded-lg bg-jd-warning/10 text-jd-warning px-3 py-2 text-xs">
            {{ __('You are in an in-app browser. Open this page in Safari first — installing only works from there.') }}
        </p>
        <ol class="mt-3 space-y-3 text-sm text-pine dark:text-mint">
            <li class="flex gap-3"><span class="font-mono text-moss dark:text-sage">1</span><span>{{ __('Tap the') }} <strong>{{ __('Share') }}</strong> {{ __('button') }}
                <svg class="inline w-4 h-4 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0-12L8 7m4-4 4 4M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>
                {{ __('in the Safari toolbar (bottom on iPhone, top on iPad).') }}</span></li>
            <li class="flex gap-3"><span class="font-mono text-moss dark:text-sage">2</span><span>{{ __('Scroll down and tap') }} <strong>{{ __('Add to Home Screen') }}</strong>.</span></li>
            <li class="flex gap-3"><span class="font-mono text-moss dark:text-sage">3</span><span>{{ __('Tap') }} <strong>{{ __('Add') }}</strong>. {{ __('JupyterDocs now opens like an app from your home screen.') }}</span></li>
        </ol>
        <button type="button" data-ios-close class="mt-4 w-full rounded-lg bg-moss dark:bg-ember text-jd-bg dark:text-pine py-2.5 text-sm font-display font-semibold active:scale-95 transition">{{ __('Got it') }}</button>
    </div>
</div>

<script>
    (function () {
        var root = document.documentElement;
        var label = document.getElementById('theme-toggle-label');
        var STORE_KEY = 'jd-theme';

        function apply(theme) {
            root.classList.toggle('dark', theme !== 'light');
            if (label) label.textContent = theme === 'light' ? 'Light' : 'Dark';
        }

        function toggle() {
            var next = root.classList.contains('dark') ? 'light' : 'dark';
            apply(next);
            try { localStorage.setItem(STORE_KEY, next); } catch (e) {}
        }

        apply(root.classList.contains('dark') ? 'dark' : 'light');

        ['theme-toggle', 'theme-toggle-mobile'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', toggle);
        });
    })();

    (function () {
        var deferredPrompt = null;
        var buttons = ['pwa-install', 'pwa-install-mobile']
            .map(function (id) { return document.getElementById(id); })
            .filter(Boolean);

        if (! buttons.length) return;

        var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (standalone) return;

        var ua = window.navigator.userAgent;
        // iPadOS 13+ reports itself as a Mac, so also check for touch support.
        var isIos = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var inAppBrowser = /FBAN|FBAV|Instagram|Line\/|MicroMessenger|Snapchat|TikTok|GSA\//i.test(ua);

        var sheet = document.getElementById('ios-install-sheet');
        var inAppNote = document.getElementById('ios-install-inapp');

        function toggleSheet(open) {
            if (! sheet) return;
            sheet.hidden = ! open;
        }

        if (isIos && sheet) {
            // Safari never fires beforeinstallprompt, so show our own guide.
            if (inAppNote) inAppNote.hidden = ! inAppBrowser;
            buttons.forEach(function (btn) {
                btn.hidden = false;
                btn.addEventListener('click', function () { toggleSheet(true); });
            });
            sheet.addEventListener('click', function (e) {
                if (e.target === sheet || e.target.closest('[data-ios-close]')) toggleSheet(false);
            });
            return;
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            buttons.forEach(function (btn) { btn.hidden = false; });
        });

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (! deferredPrompt) return;

                deferredPrompt.prompt();
                deferredPrompt.userChoice.finally(function () {
                    deferredPrompt = null;
                    buttons.forEach(function (b) { b.hidden = true; });
                });
            });
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            buttons.forEach(function (btn) { btn.hidden = true; });
        });
    })();
</script>
