<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Admin Overview') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            {{-- Key stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-admin.stat-card label="Total Users" :value="$stats['total_users']" />
                <x-admin.stat-card label="Online Now" :value="$stats['online_now']" accent="jd-success" />
                <x-admin.stat-card label="Avg Daily Active (30d)" :value="$stats['avg_daily_active_users']" />
                <x-admin.stat-card label="New Users (7d)" :value="$stats['new_users_7d']" />
                <x-admin.stat-card label="Total Resources" :value="$stats['total_resources']" />
                <x-admin.stat-card label="Pending Review" :value="$stats['pending_review']" accent="jd-warning" :href="route('admin.moderation.index')" />
                <x-admin.stat-card label="Approved" :value="$stats['approved']" accent="jd-success" />
                <x-admin.stat-card label="Rejected" :value="$stats['rejected']" accent="jd-danger" />
                <x-admin.stat-card label="Needs PDF Conversion" :value="$stats['needs_conversion']" accent="jd-warning" :href="route('admin.content.index')" />
                <x-admin.stat-card label="Converting Now" :value="$stats['converting']" />
                <x-admin.stat-card label="Total Downloads" :value="$stats['total_downloads']" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Recent users --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="flex items-center justify-between p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Newest Users') }}</h3>
                        <a href="{{ route('admin.users.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('View all') }} &rarr;</a>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($recentUsers as $user)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-display font-medium text-pine dark:text-mint">{{ $user->name }}</div>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $user->email }} &middot; {{ $user->created_at->diffForHumans() }}</div>
                                </div>
                                <span class="text-xs font-display font-semibold px-2.5 py-1 rounded-full bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage">{{ ucfirst($user->role) }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No users yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Recent uploads --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                    <div class="flex items-center justify-between p-4 border-b border-pine/10 dark:border-mint/10">
                        <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Recent Uploads') }}</h3>
                        <a href="{{ route('admin.content.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint">{{ __('View all') }} &rarr;</a>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @forelse ($recentUploads as $resource)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $resource->resourceType->name }} &middot; {{ $resource->uploaderDisplayName() }} &middot; {{ $resource->created_at->diffForHumans() }}</div>
                                </div>
                                <span class="text-[10px] font-mono font-bold uppercase text-jd-ink-muted dark:text-sage">{{ $resource->format }}</span>
                            </div>
                        @empty
                            <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No uploads yet.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($failedConversions->isNotEmpty())
                <div class="bg-white dark:bg-pine border border-jd-danger/20 shadow-sm rounded-xl">
                    <div class="p-4 border-b border-jd-danger/20">
                        <h3 class="font-display font-semibold text-jd-danger">{{ __('Failed PDF Conversions') }}</h3>
                    </div>
                    <div class="divide-y divide-pine/10 dark:divide-mint/10">
                        @foreach ($failedConversions as $resource)
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                    <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ __('by') }} {{ $resource->uploaderDisplayName() }} &middot; {{ strtoupper($resource->format) }}</div>
                                    @if ($resource->conversion_error)
                                        <div class="text-xs text-jd-danger mt-1">{{ Str::limit($resource->conversion_error, 140) }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('admin.content.index') }}" class="text-xs font-display font-semibold text-jd-ink-muted dark:text-sage hover:text-pine dark:hover:text-mint shrink-0">{{ __('Review') }} &rarr;</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
