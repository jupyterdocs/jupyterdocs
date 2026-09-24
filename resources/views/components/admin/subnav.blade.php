@php
    $tabs = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => __('Overview')],
        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => __('Users')],
        ['route' => 'admin.content.index', 'pattern' => 'admin.content.*', 'label' => __('Library Analytics')],
        ['route' => 'admin.traffic.index', 'pattern' => 'admin.traffic.*', 'label' => __('Traffic')],
        ['route' => 'admin.conversion.index', 'pattern' => 'admin.conversion.*', 'label' => __('Conversion')],
        ['route' => 'admin.moderation.index', 'pattern' => 'admin.moderation.*', 'label' => __('Moderation')],
        ['route' => 'admin.reports.index', 'pattern' => 'admin.reports.*', 'label' => __('Reports')],
    ];
@endphp

<div class="flex flex-wrap gap-2 border-b border-pine/10 dark:border-mint/10 pb-3">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           @class([
               'px-3 py-1.5 rounded-lg text-sm font-display font-semibold transition',
               'bg-moss dark:bg-sage text-jd-bg dark:text-pine' => request()->routeIs($tab['pattern']),
               'text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => ! request()->routeIs($tab['pattern']),
           ])
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
