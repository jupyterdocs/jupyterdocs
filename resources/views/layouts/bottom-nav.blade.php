<nav class="sm:hidden fixed bottom-0 inset-x-0 z-20 safe-bottom bg-white/95 dark:bg-pine/95 backdrop-blur border-t border-pine/10 dark:border-mint/10">
    <div class="grid grid-cols-4">
        @php
            $tabs = [
                ['route' => 'resources.index', 'active' => request()->routeIs('resources.index'), 'label' => __('Browse'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0a7.5 7.5 0 1 0-10.6 0 7.5 7.5 0 0 0 10.6 0Z"/>'],
                ['route' => 'resources.create', 'active' => request()->routeIs('resources.create'), 'label' => __('Upload'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/>'],
                ['route' => Auth::check() ? 'resources.saved' : 'login', 'active' => request()->routeIs('resources.saved'), 'label' => __('Saved'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>'],
                ['route' => Auth::check() ? 'profile.edit' : 'login', 'active' => request()->routeIs('profile.edit'), 'label' => Auth::check() ? __('Account') : __('Log in'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0a9 9 0 1 0-11.964 0m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>'],
            ];
        @endphp

        @foreach ($tabs as $tab)
            <a href="{{ route($tab['route']) }}" class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-display font-medium transition-transform active:scale-90 {{ $tab['active'] ? 'text-moss dark:text-sage' : 'text-jd-ink-muted dark:text-sage/60' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $tab['active'] ? 2.2 : 1.8 }}">{!! $tab['icon'] !!}</svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
