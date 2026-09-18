<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Reported Documents') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-admin.subnav />

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @foreach (['open' => __('Open').' ('.$openCount.')', 'resolved' => __('Resolved'), 'dismissed' => __('Dismissed'), 'all' => __('All')] as $key => $label)
                    <a href="{{ route('admin.reports.index', ['status' => $key]) }}"
                       @class([
                           'px-3 py-1.5 rounded-full text-xs font-display font-semibold border transition',
                           'bg-moss text-jd-bg border-moss dark:bg-ember dark:text-pine dark:border-sage' => $status === $key,
                           'border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => $status !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl divide-y divide-pine/10 dark:divide-mint/10">
                @forelse ($reports as $report)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                @if ($report->resource && ! $report->resource->trashed())
                                    <a href="{{ route('resources.show', $report->resource) }}" class="font-display font-medium text-pine dark:text-mint hover:underline">{{ $report->resource->title }}</a>
                                @else
                                    <span class="font-display font-medium text-jd-ink-muted dark:text-sage">{{ $report->resource?->title ?? __('Deleted document') }}</span>
                                @endif
                                <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">
                                    {{ $report->resource?->resourceType?->name }}
                                    &middot; {{ __('reported by') }} {{ $report->user->name }}
                                    &middot; {{ $report->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span @class([
                                'shrink-0 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                                'bg-jd-warning/10 text-jd-warning' => $report->status === 'open',
                                'bg-jd-success/10 text-jd-success' => $report->status === 'resolved',
                                'bg-pine/10 text-jd-ink-muted dark:bg-mint/10 dark:text-sage' => $report->status === 'dismissed',
                            ])>{{ ucfirst($report->status) }}</span>
                        </div>

                        <div class="text-sm">
                            <span class="font-display font-semibold text-jd-danger">{{ $report->reasonLabel() }}</span>
                            @if ($report->details)
                                <p class="mt-1 text-jd-ink-muted dark:text-sage font-serif whitespace-pre-line">{{ $report->details }}</p>
                            @endif
                        </div>

                        @if ($report->status === 'open')
                            <div class="flex items-center gap-2 pt-1">
                                <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-xs font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright">{{ __('Mark resolved') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-transparent border border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage rounded-lg text-xs font-display font-semibold hover:bg-jd-surface-2 dark:hover:bg-cypress">{{ __('Dismiss') }}</button>
                                </form>
                            </div>
                        @elseif ($report->reviewer)
                            <p class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ ucfirst($report->status) }} {{ __('by') }} {{ $report->reviewer->name }} &middot; {{ $report->reviewed_at?->diffForHumans() }}</p>
                        @endif
                    </div>
                @empty
                    <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No reports here.') }}</p>
                @endforelse
            </div>

            {{ $reports->links() }}
        </div>
    </div>
</x-app-layout>
