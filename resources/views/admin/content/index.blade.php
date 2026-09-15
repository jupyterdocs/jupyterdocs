<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Library Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <x-admin.stat-card label="Total Documents" :value="$totals['total_resources']" />
                <x-admin.stat-card label="Total Downloads" :value="$totals['total_downloads']" />
                <div class="block bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <div class="text-xs font-display font-medium text-jd-ink-muted dark:text-sage uppercase tracking-wide">{{ __('Storage Used') }}</div>
                    <div class="mt-1 text-2xl font-display font-bold text-pine dark:text-mint">{{ \App\Support\Format::bytes($totals['total_storage_bytes']) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- By file format --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('By File Type') }}</h3>
                    <div class="space-y-2">
                        @forelse ($byFormat as $format => $row)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-mono uppercase text-jd-ink-muted dark:text-sage">{{ $format }}</span>
                                <span class="text-right">
                                    <span class="font-display font-semibold text-pine dark:text-mint">{{ $row->total }}</span>
                                    <span class="block text-[11px] font-mono text-jd-ink-muted dark:text-sage">{{ \App\Support\Format::bytes($row->storage_bytes) }}</span>
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('No documents yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- By resource type (category) --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('By Category') }}</h3>
                    <div class="space-y-2">
                        @forelse ($byType as $row)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-jd-ink-muted dark:text-sage">{{ $row['name'] }}</span>
                                <span class="font-display font-semibold text-pine dark:text-mint">{{ $row['total'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-jd-ink-muted dark:text-sage">{{ __('No documents yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- By moderation status --}}
                <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                    <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('By Status') }}</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-jd-warning">{{ __('Pending') }}</span>
                            <span class="font-display font-semibold text-pine dark:text-mint">{{ $byStatus['pending'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-jd-success">{{ __('Approved') }}</span>
                            <span class="font-display font-semibold text-pine dark:text-mint">{{ $byStatus['approved'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-jd-danger">{{ __('Rejected') }}</span>
                            <span class="font-display font-semibold text-pine dark:text-mint">{{ $byStatus['rejected'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conversion status breakdown --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl p-4">
                <h3 class="font-display font-semibold text-pine dark:text-mint mb-3">{{ __('PDF Conversion Status (non-PDF documents)') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress p-3 text-center">
                        <div class="font-display font-bold text-lg text-pine dark:text-mint">{{ $byConversion['none'] ?? 0 }}</div>
                        <div class="text-xs text-jd-ink-muted dark:text-sage">{{ __('Not started') }}</div>
                    </div>
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress p-3 text-center">
                        <div class="font-display font-bold text-lg text-jd-warning">{{ $byConversion['pending'] ?? 0 }}</div>
                        <div class="text-xs text-jd-ink-muted dark:text-sage">{{ __('Queued') }}</div>
                    </div>
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress p-3 text-center">
                        <div class="font-display font-bold text-lg text-jd-warning">{{ $byConversion['processing'] ?? 0 }}</div>
                        <div class="text-xs text-jd-ink-muted dark:text-sage">{{ __('Processing') }}</div>
                    </div>
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress p-3 text-center">
                        <div class="font-display font-bold text-lg text-jd-success">{{ $byConversion['done'] ?? 0 }}</div>
                        <div class="text-xs text-jd-ink-muted dark:text-sage">{{ __('Done') }}</div>
                    </div>
                    <div class="rounded-lg bg-jd-surface-2 dark:bg-cypress p-3 text-center">
                        <div class="font-display font-bold text-lg text-jd-danger">{{ $byConversion['failed'] ?? 0 }}</div>
                        <div class="text-xs text-jd-ink-muted dark:text-sage">{{ __('Failed') }}</div>
                    </div>
                </div>
            </div>

            {{-- Documents that still need conversion --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                    <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Documents Needing Conversion') }}</h3>
                </div>
                <div class="divide-y divide-pine/10 dark:divide-mint/10">
                    @forelse ($needsConversion as $resource)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                                <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">
                                    {{ strtoupper($resource->format) }} &middot; {{ __('by') }} {{ $resource->uploaderDisplayName() }} &middot; {{ $resource->created_at->diffForHumans() }}
                                </div>
                                @if ($resource->conversion_status === 'failed' && $resource->conversion_error)
                                    <div class="text-xs text-jd-danger mt-1">{{ Str::limit($resource->conversion_error, 140) }}</div>
                                @endif
                            </div>
                            <span @class([
                                'text-xs font-display font-semibold px-2.5 py-1 rounded-full shrink-0',
                                'bg-jd-surface-2 dark:bg-cypress text-jd-ink-muted dark:text-sage' => $resource->conversion_status === 'none',
                                'bg-jd-warning/10 text-jd-warning' => in_array($resource->conversion_status, ['pending', 'processing']),
                                'bg-jd-danger/10 text-jd-danger' => $resource->conversion_status === 'failed',
                            ])>
                                {{ ucfirst($resource->conversion_status) }}
                            </span>
                        </div>
                    @empty
                        <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('Nothing needs converting.') }}</p>
                    @endforelse
                </div>
                {{ $needsConversion->links() }}
            </div>

            {{-- Top downloaded --}}
            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl">
                <div class="p-4 border-b border-pine/10 dark:border-mint/10">
                    <h3 class="font-display font-semibold text-pine dark:text-mint">{{ __('Most Downloaded') }}</h3>
                </div>
                <div class="divide-y divide-pine/10 dark:divide-mint/10">
                    @forelse ($topDownloaded as $resource)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <a href="{{ route('resources.show', $resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $resource->title }}</a>
                            <span class="font-mono text-sm text-jd-ink-muted dark:text-sage">{{ $resource->downloads_count }} {{ __('downloads') }}</span>
                        </div>
                    @empty
                        <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No downloads yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
