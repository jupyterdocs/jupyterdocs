<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
            {{ __('Requested Documents') }}
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
                @foreach (['open' => __('Open').' ('.$openCount.')', 'fulfilled' => __('Fulfilled'), 'declined' => __('Declined'), 'all' => __('All')] as $key => $label)
                    <a href="{{ route('admin.requests.index', ['status' => $key]) }}"
                       @class([
                           'px-3 py-1.5 rounded-full text-xs font-display font-semibold border transition',
                           'bg-moss text-jd-bg border-moss dark:bg-ember dark:text-pine dark:border-sage' => $status === $key,
                           'border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => $status !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl divide-y divide-pine/10 dark:divide-mint/10">
                @forelse ($requests as $resourceRequest)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <span class="font-display font-medium text-pine dark:text-mint">{{ $resourceRequest->title }}</span>
                                <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">
                                    {{ $resourceRequest->upvotes_count }} {{ \Illuminate\Support\Str::plural('upvote', $resourceRequest->upvotes_count) }}
                                    &middot; {{ $resourceRequest->resourceType?->name }}
                                    @if ($resourceRequest->course) &middot; {{ $resourceRequest->course->name }} @endif
                                    @if ($resourceRequest->university) &middot; {{ $resourceRequest->university->name }} @endif
                                    &middot; {{ __('requested by') }} {{ $resourceRequest->user->name }}
                                    &middot; {{ $resourceRequest->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span @class([
                                'shrink-0 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                                'bg-jd-warning/10 text-jd-warning' => $resourceRequest->status === 'open',
                                'bg-jd-success/10 text-jd-success' => $resourceRequest->status === 'fulfilled',
                                'bg-pine/10 text-jd-ink-muted dark:bg-mint/10 dark:text-sage' => $resourceRequest->status === 'declined',
                            ])>{{ $resourceRequest->statusLabel() }}</span>
                        </div>

                        @if ($resourceRequest->description)
                            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif whitespace-pre-line">{{ $resourceRequest->description }}</p>
                        @endif

                        @if ($resourceRequest->status === 'open')
                            <div class="flex items-center gap-2 pt-1">
                                <form method="POST" action="{{ route('admin.requests.update', $resourceRequest) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="fulfilled">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-xs font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright">{{ __('Mark fulfilled') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.requests.update', $resourceRequest) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="declined">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-transparent border border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage rounded-lg text-xs font-display font-semibold hover:bg-jd-surface-2 dark:hover:bg-cypress">{{ __('Decline') }}</button>
                                </form>
                            </div>
                        @elseif ($resourceRequest->reviewer)
                            <p class="text-xs text-jd-ink-muted dark:text-sage font-mono">{{ $resourceRequest->statusLabel() }} {{ __('by') }} {{ $resourceRequest->reviewer->name }} &middot; {{ $resourceRequest->reviewed_at?->diffForHumans() }}</p>
                        @endif
                    </div>
                @empty
                    <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No requests here.') }}</p>
                @endforelse
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
