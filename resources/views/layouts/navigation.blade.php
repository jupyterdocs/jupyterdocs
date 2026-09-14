<nav x-data="{ open: false }" class="bg-jd-bg/90 dark:bg-abyss/90 backdrop-blur border-b border-pine/10 dark:border-mint/10 sticky top-0 z-20">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-pine dark:text-mint">
                        <svg class="w-7 h-7" viewBox="0 0 640 640"><use href="#jupyterMark"/></svg>
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
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.moderation.index')" :active="request()->routeIs('admin.moderation.*')">
                                {{ __('Moderation') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
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
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-moss text-jd-bg rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:bg-sage dark:text-pine dark:hover:bg-mint transition">{{ __('Register') }}</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden gap-1">
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
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.moderation.index')" :active="request()->routeIs('admin.moderation.*')">
                        {{ __('Moderation') }}
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
</script>
