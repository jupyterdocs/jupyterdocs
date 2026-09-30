<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-display font-semibold text-xl text-pine dark:text-mint leading-tight">
                {{ __('Requested Documents') }}
            </h2>
            <a href="{{ route('requests.create') }}" class="inline-flex items-center px-4 py-2 bg-moss dark:bg-ember text-jd-bg dark:text-pine rounded-lg text-sm font-display font-semibold hover:bg-cypress dark:hover:bg-ember-bright active:scale-95 transition">
                {{ __('Request a document') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg bg-jd-success/10 border border-jd-success/20 text-jd-success px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-sm text-jd-ink-muted dark:text-sage font-serif">
                {{ __("Can't find a document you need? Ask for it here, or upvote a request someone already made so we know it's in demand.") }}
            </p>

            <div class="flex flex-wrap gap-2">
                @foreach (['open' => __('Open'), 'fulfilled' => __('Fulfilled'), 'declined' => __('Declined'), 'all' => __('All')] as $key => $label)
                    <a href="{{ route('requests.index', ['status' => $key]) }}"
                       @class([
                           'px-3 py-1.5 rounded-full text-xs font-display font-semibold border transition',
                           'bg-moss text-jd-bg border-moss dark:bg-ember dark:text-pine dark:border-sage' => $status === $key,
                           'border-pine/20 dark:border-mint/20 text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => $status !== $key,
                       ])>{{ $label }}</a>
                @endforeach
            </div>

            <div class="bg-white dark:bg-pine border border-pine/10 dark:border-mint/10 shadow-sm rounded-xl divide-y divide-pine/10 dark:divide-mint/10">
                @forelse ($requests as $resourceRequest)
                    <div class="p-4 flex items-start gap-4">
                        <form method="POST" action="{{ route('requests.upvote', $resourceRequest) }}" class="shrink-0">
                            @csrf
                            <button type="submit"
                                @disabled($resourceRequest->status !== 'open')
                                @class([
                                    'flex flex-col items-center justify-center w-14 h-14 rounded-lg border font-display transition',
                                    'border-moss dark:border-sage bg-moss/10 dark:bg-sage/10 text-moss dark:text-sage' => in_array($resourceRequest->id, $votedIds),
                                    'border-pine/15 dark:border-mint/15 text-jd-ink-muted dark:text-sage hover:bg-jd-surface-2 dark:hover:bg-cypress' => ! in_array($resourceRequest->id, $votedIds),
                                    'opacity-50 cursor-not-allowed' => $resourceRequest->status !== 'open',
                                ])>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
                                <span class="text-sm font-bold leading-none mt-1">{{ $resourceRequest->upvotes_count }}</span>
                            </button>
                        </form>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="font-display font-medium text-pine dark:text-mint">{{ $resourceRequest->title }}</h3>
                                <span @class([
                                    'shrink-0 text-xs font-display font-semibold px-2.5 py-1 rounded-full',
                                    'bg-jd-warning/10 text-jd-warning' => $resourceRequest->status === 'open',
                                    'bg-jd-success/10 text-jd-success' => $resourceRequest->status === 'fulfilled',
                                    'bg-pine/10 text-jd-ink-muted dark:bg-mint/10 dark:text-sage' => $resourceRequest->status === 'declined',
                                ])>{{ $resourceRequest->statusLabel() }}</span>
                            </div>

                            <div class="text-xs text-jd-ink-muted dark:text-sage font-mono">
                                {{ $resourceRequest->resourceType?->name }}
                                @if ($resourceRequest->course) &middot; {{ $resourceRequest->course->name }} @endif
                                @if ($resourceRequest->university) &middot; {{ $resourceRequest->university->name }} @endif
                                &middot; {{ __('requested by') }} {{ $resourceRequest->user->name }}
                                &middot; {{ $resourceRequest->created_at->diffForHumans() }}
                            </div>

                            @if ($resourceRequest->description)
                                <p class="mt-1 text-sm text-jd-ink-muted dark:text-sage font-serif whitespace-pre-line">{{ $resourceRequest->description }}</p>
                            @endif

                            @if ($resourceRequest->status === 'fulfilled' && $resourceRequest->fulfilledResource && ! $resourceRequest->fulfilledResource->trashed())
                                <a href="{{ route('resources.show', $resourceRequest->fulfilledResource) }}" class="mt-1 inline-block text-sm font-display font-semibold text-moss dark:text-sage hover:text-cypress dark:hover:text-mint">
                                    {{ __('View the document →') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-jd-ink-muted dark:text-sage">{{ __('No requests here.') }}</p>
                @endforelse
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
